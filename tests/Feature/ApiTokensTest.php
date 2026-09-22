<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Livewire\ApiTokensTable;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\CentralTokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\TokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use Mortalkiller\FilamentCompleteUserProfile\Tokens\TokenManager;

class ApiTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSchema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        DatabaseSchema::create('personal_access_tokens', function (Blueprint $table): void {
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

    public function test_tenant_context_readiness_uses_the_sanctum_token_model_connection(): void
    {
        config()->set('database.connections.tokens', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DatabaseSchema::connection('tokens')->create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('context_type')->nullable()->index();
            $table->string('context_id')->nullable()->index();
            $table->timestamps();
        });

        self::assertFalse(DatabaseSchema::hasColumn('personal_access_tokens', 'context_type'));
        self::assertTrue(DatabaseSchema::connection('tokens')->hasColumn('personal_access_tokens', 'context_type'));

        $feature = ApiTokens::make()
            ->enabled()
            ->tenantScoped()
            ->abilities(['customers:read' => 'Read customers']);

        self::assertNull($feature->getRequirementIssue(new CentralTokenUser));
    }

    public function test_api_tokens_component_uses_native_table_and_plaintext_only_once(): void
    {
        config()->set('auth.guards.profile', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => TokenUser::class]);

        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        auth('profile')->setUser($user);

        $plugin = CompleteUserProfilePlugin::make()
            ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                ->abilities(['customers:read' => 'Read customers'])
                ->defaultExpiration(7)
                ->maxExpiration(30));

        Filament::setCurrentPanel(
            Panel::make()
                ->id('admin')
                ->authGuard('profile')
                ->plugin($plugin),
        );

        $component = new ApiTokensTable;
        $table = $component->table(Table::make($component));

        self::assertSame(
            ['name', 'abilities', 'last_used_at', 'expires_at'],
            array_keys($table->getColumns()),
        );

        $headerActions = array_values($table->getHeaderActions());
        self::assertInstanceOf(Action::class, $headerActions[0] ?? null);
        self::assertSame('create', $headerActions[0]->getName());

        $recordAction = $table->getAction('revoke');
        self::assertNotNull($recordAction);
        self::assertSame('revoke', $recordAction->getName());

        $showTokenAction = $component->showCreatedTokenAction();
        self::assertFalse($showTokenAction->isModalClosedByClickingAway());
        self::assertFalse($showTokenAction->isModalClosedByEscaping());

        $component->createToken([
            'name' => 'CLI',
            'abilities' => ['customers:read'],
            'expiration' => 7,
        ]);

        $plainTextToken = $component->getCreatedPlainTextToken();
        self::assertNotNull($plainTextToken);
        self::assertStringContainsString('|', $plainTextToken);
        self::assertNotSame($plainTextToken, $user->tokens()->first()?->getAttribute('token'));

        $component->dismissCreatedToken();
        self::assertNull($component->getCreatedPlainTextToken());
    }

    public function test_created_token_uses_a_read_only_masked_input_with_native_copy_and_reveal(): void
    {
        $component = $this->makeAuthenticatedComponent();

        $component->createToken([
            'name' => 'CLI',
            'abilities' => ['customers:read'],
            'expiration' => 7,
        ]);

        $plainTextToken = $component->getCreatedPlainTextToken();
        self::assertNotNull($plainTextToken);

        $callout = $this->getCreatedTokenWarningCallout($component);
        self::assertSame('warning', $callout->getStatus());
        self::assertNotNull($callout->getHeading());
        self::assertNotNull($callout->getDescription());

        $input = $this->getCreatedTokenInput($component);
        self::assertTrue($input->isReadOnly());
        self::assertTrue($input->isPassword());
        self::assertTrue($input->isCopyable());
        self::assertTrue($input->isPasswordRevealable());

        self::assertSame(
            ['copy', 'showPassword', 'hidePassword'],
            array_keys($input->getSuffixActions()),
        );

        // The modal must carry the token in the mounted action's own state, not in a
        // protected component property: Livewire discards those between requests, which
        // is what made the token vanish on the first interaction with the modal.
        $action = $component->showCreatedTokenAction();
        $schema = $action->getSchema(Schema::make($component));
        self::assertNotNull($schema);

        $action->mount(['form' => $schema, 'schema' => $schema]);

        self::assertSame($plainTextToken, $schema->getState()['plain_text_token'] ?? null);
    }

    public function test_the_created_token_reveal_never_needs_a_server_round_trip(): void
    {
        $component = $this->makeAuthenticatedComponent();

        $component->createToken([
            'name' => 'CLI',
            'abilities' => ['customers:read'],
            'expiration' => 7,
        ]);

        $suffixActions = $this->getCreatedTokenInput($component)->getSuffixActions();

        foreach (['copy', 'showPassword', 'hidePassword'] as $name) {
            $action = $suffixActions[$name] ?? null;

            self::assertInstanceOf(Action::class, $action);
            self::assertNotNull(
                $action->getAlpineClickHandler(),
                "[{$name}] must toggle or copy in the browser, not through Livewire.",
            );
        }
    }

    private function makeAuthenticatedComponent(): ApiTokensTable
    {
        config()->set('auth.guards.profile', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => TokenUser::class]);

        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        auth('profile')->setUser($user);

        $plugin = CompleteUserProfilePlugin::make()
            ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                ->abilities(['customers:read' => 'Read customers'])
                ->defaultExpiration(7)
                ->maxExpiration(30));

        Filament::setCurrentPanel(
            Panel::make()
                ->id('admin')
                ->authGuard('profile')
                ->plugin($plugin),
        );

        return new ApiTokensTable;
    }

    private function getCreatedTokenWarningCallout(ApiTokensTable $component): Callout
    {
        $components = $this->getCreatedTokenSchemaComponents($component);

        self::assertInstanceOf(Callout::class, $components[0]);

        return $components[0];
    }

    private function getCreatedTokenInput(ApiTokensTable $component): TextInput
    {
        $components = $this->getCreatedTokenSchemaComponents($component);

        self::assertInstanceOf(TextInput::class, $components[1]);

        return $components[1];
    }

    /** @return array<int, Component|Action|ActionGroup> */
    private function getCreatedTokenSchemaComponents(ApiTokensTable $component): array
    {
        $action = $component->showCreatedTokenAction();
        $components = array_values($action->getSchema(Schema::make($component))?->getComponents() ?? []);

        self::assertCount(2, $components);

        return $components;
    }
}
