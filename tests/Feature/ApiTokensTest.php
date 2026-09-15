<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\TokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use Mortalkiller\FilamentCompleteUserProfile\Tokens\TokenManager;

class ApiTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_api_token_configuration_has_safe_expiration_controls(): void
    {
        $feature = ApiTokens::make()
            ->abilities(['customers:read' => 'Read customers'])
            ->defaultExpiration(7)
            ->maxExpiration(30);

        self::assertSame(['customers:read' => 'Read customers'], $feature->getAbilities());
        self::assertSame(7, $feature->getDefaultExpiration());
        self::assertSame(30, $feature->getMaxExpiration());
    }

    public function test_creation_is_blocked_when_the_ability_whitelist_is_empty(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);

        $this->expectException(ValidationException::class);

        app(TokenManager::class)->create(
            $user,
            ApiTokens::make(),
            'CLI',
            ['customers:read'],
        );
    }

    public function test_token_creation_uses_only_whitelisted_abilities_and_default_expiration(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-15 12:00:00'));

        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $feature = ApiTokens::make()
            ->abilities([
                'customers:read' => 'Read customers',
                'customers:write' => 'Manage customers',
            ])
            ->defaultExpiration(7)
            ->maxExpiration(30);

        $token = app(TokenManager::class)->create(
            $user,
            $feature,
            'CLI',
            ['customers:read'],
        );

        self::assertStringContainsString('|', $token->plainTextToken);
        self::assertSame(['customers:read'], $token->accessToken->abilities);
        self::assertNotContains('*', $token->accessToken->abilities ?? []);
        self::assertSame(
            '2026-09-22 12:00:00',
            $token->accessToken->expires_at?->format('Y-m-d H:i:s'),
        );
    }

    public function test_unlisted_and_wildcard_abilities_are_rejected(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $feature = ApiTokens::make()->abilities([
            'customers:read' => 'Read customers',
        ]);
        $manager = app(TokenManager::class);

        foreach ([['customers:write'], ['*']] as $abilities) {
            try {
                $manager->create($user, $feature, 'Invalid', $abilities);
                self::fail('Expected invalid token abilities to be rejected.');
            } catch (ValidationException) {
                self::assertSame(0, $user->tokens()->count());
            }
        }
    }

    public function test_expiration_cannot_exceed_the_configured_maximum(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $feature = ApiTokens::make()
            ->abilities(['customers:read' => 'Read customers'])
            ->maxExpiration(30);

        $this->expectException(ValidationException::class);

        app(TokenManager::class)->create(
            $user,
            $feature,
            'Too long',
            ['customers:read'],
            31,
        );
    }

    public function test_revoke_is_always_scoped_to_the_authenticated_user(): void
    {
        $first = TokenUser::query()->create(['email' => 'first@example.test']);
        $second = TokenUser::query()->create(['email' => 'second@example.test']);
        $feature = ApiTokens::make()->abilities([
            'customers:read' => 'Read customers',
        ]);
        $manager = app(TokenManager::class);

        $firstToken = $manager->create($first, $feature, 'First', ['customers:read']);
        $secondToken = $manager->create($second, $feature, 'Second', ['customers:read']);

        $manager->revoke($first, (string) $secondToken->accessToken->getKey());
        self::assertTrue($second->tokens()->whereKey($secondToken->accessToken->getKey())->exists());

        $manager->revoke($first, (string) $firstToken->accessToken->getKey());
        self::assertFalse($first->tokens()->whereKey($firstToken->accessToken->getKey())->exists());
    }

    public function test_feature_reports_when_the_user_does_not_support_sanctum_tokens(): void
    {
        $feature = ApiTokens::make()
            ->enabled()
            ->abilities(['customers:read' => 'Read customers']);

        self::assertNotNull($feature->getRequirementIssue(new User));
        self::assertNull($feature->getRequirementIssue(new TokenUser));
    }
}
