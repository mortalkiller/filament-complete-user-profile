<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Http\Middleware\EnsureTokenContext;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\MutableTenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\Tenant;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\TokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use Mortalkiller\FilamentCompleteUserProfile\Tokens\TokenManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantScopedTokensTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Panel::make()->id('admin'));

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
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
            $table->string('context_type')->nullable();
            $table->string('context_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_tenant_context_is_persisted_and_required_for_creation(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenant = Tenant::query()->create(['name' => 'Tenant A']);
        $feature = $this->feature();
        $manager = app(TokenManager::class);

        Filament::setTenant($tenant, isQuiet: true);

        $token = $manager->create($user, $feature, 'Tenant A CLI', ['customers:read']);

        self::assertSame($tenant->getMorphClass(), $token->accessToken->getAttribute('context_type'));
        self::assertSame((string) $tenant->getKey(), (string) $token->accessToken->getAttribute('context_id'));

        Filament::setTenant(null, isQuiet: true);
        $this->expectException(ValidationException::class);

        $manager->create($user, $feature, 'No context', ['customers:read']);
    }

    public function test_listing_and_revocation_are_scoped_to_active_tenant(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenantA = Tenant::query()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::query()->create(['name' => 'Tenant B']);
        $feature = $this->feature();
        $manager = app(TokenManager::class);

        Filament::setTenant($tenantA, isQuiet: true);
        $tokenA = $manager->create($user, $feature, 'A', ['customers:read']);

        Filament::setTenant($tenantB, isQuiet: true);
        $tokenB = $manager->create($user, $feature, 'B', ['customers:read']);

        Filament::setTenant($tenantA, isQuiet: true);
        $visible = $manager->tokensFor($user, $feature);

        self::assertSame([(string) $tokenA->accessToken->getKey()], $visible->pluck('id')->map(fn ($id): string => (string) $id)->all());

        $manager->revoke($user, (string) $tokenB->accessToken->getKey(), $feature);
        self::assertTrue($user->tokens()->whereKey($tokenB->accessToken->getKey())->exists());

        $manager->revoke($user, (string) $tokenA->accessToken->getKey(), $feature);
        self::assertFalse($user->tokens()->whereKey($tokenA->accessToken->getKey())->exists());
    }

    public function test_missing_context_columns_fail_closed(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropColumn(['context_type', 'context_id']);
        });

        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenant = Tenant::query()->create(['name' => 'Tenant A']);
        Filament::setTenant($tenant, isQuiet: true);

        $this->expectException(ValidationException::class);

        app(TokenManager::class)->create($user, $this->feature(), 'CLI', ['customers:read']);
    }

    public function test_api_middleware_rejects_mismatched_or_missing_context(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenantA = Tenant::query()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::query()->create(['name' => 'Tenant B']);
        $manager = app(TokenManager::class);

        Filament::setTenant($tenantA, isQuiet: true);
        $token = $manager->create($user, $this->feature(), 'CLI', ['customers:read']);
        $user->withAccessToken($token->accessToken);

        $resolver = new class implements TokenContextResolver
        {
            public ?Model $context = null;

            public function resolve(): ?Model
            {
                return $this->context;
            }
        };
        $resolver->context = $tenantB;
        app()->instance(TokenContextResolver::class, $resolver);

        $request = Request::create('/api/customers');
        $request->setUserResolver(fn (): TokenUser => $user);

        try {
            app(EnsureTokenContext::class)->handle($request, fn () => response('ok'));
            self::fail('Expected a mismatched API token context to be rejected.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }

        $resolver->context = null;

        $this->expectException(HttpException::class);
        app(EnsureTokenContext::class)->handle($request, fn () => response('ok'));
    }

    public function test_custom_tenancy_resolver_is_used_for_management_and_api_requests(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenantA = Tenant::query()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::query()->create(['name' => 'Tenant B']);

        $resolver = new class($tenantA) implements TenancyResolver
        {
            public function __construct(
                public ?Model $tenant,
            ) {}

            public function resolve(): ?Model
            {
                return $this->tenant;
            }
        };

        $panel = Panel::make()
            ->id('custom-tenancy')
            ->plugin(
                CompleteUserProfilePlugin::make()
                    ->tenancyResolver($resolver),
            );

        Filament::setCurrentPanel($panel);
        Filament::setTenant(null, isQuiet: true);

        $token = app(TokenManager::class)->create(
            $user,
            $this->feature(),
            'Custom tenancy CLI',
            ['customers:read'],
        );

        self::assertSame($tenantA->getMorphClass(), $token->accessToken->getAttribute('context_type'));
        self::assertSame((string) $tenantA->getKey(), (string) $token->accessToken->getAttribute('context_id'));

        $user->withAccessToken($token->accessToken);

        $request = Request::create('/api/customers');
        $request->setUserResolver(fn (): TokenUser => $user);

        $response = app(EnsureTokenContext::class)->handle($request, fn () => response('ok'));

        self::assertSame(200, $response->getStatusCode());

        $resolver->tenant = $tenantB;

        try {
            app(EnsureTokenContext::class)->handle($request, fn () => response('ok'));
            self::fail('Expected the custom tenancy resolver mismatch to be rejected.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_real_bearer_token_can_only_access_its_own_tenant_context(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenantA = Tenant::query()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::query()->create(['name' => 'Tenant B']);
        $resolver = new MutableTenancyResolver($tenantA);

        $this->registerCustomTenancyPanel($resolver);
        $this->registerTenantProtectedRoute();

        $token = app(TokenManager::class)->create(
            $user,
            $this->feature(),
            'Tenant A CLI',
            ['customers:read'],
        );

        $this->withToken($token->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertOk()
            ->assertJson([
                'token_id' => (string) $token->accessToken->getKey(),
                'tenant_id' => (string) $tenantA->getKey(),
            ]);

        $resolver->tenant = $tenantB;

        $this->withToken($token->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertForbidden();

        $resolver->tenant = $tenantA;

        $this->withToken($token->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertOk()
            ->assertJson([
                'token_id' => (string) $token->accessToken->getKey(),
                'tenant_id' => (string) $tenantA->getKey(),
            ]);
    }

    public function test_real_bearer_tokens_are_isolated_between_two_tenants(): void
    {
        $user = TokenUser::query()->create(['email' => 'pedro@example.test']);
        $tenantA = Tenant::query()->create(['name' => 'Tenant A']);
        $tenantB = Tenant::query()->create(['name' => 'Tenant B']);
        $resolver = new MutableTenancyResolver($tenantA);

        $this->registerCustomTenancyPanel($resolver);
        $this->registerTenantProtectedRoute();

        $tokenA = app(TokenManager::class)->create(
            $user,
            $this->feature(),
            'Tenant A CLI',
            ['customers:read'],
        );

        $resolver->tenant = $tenantB;

        $tokenB = app(TokenManager::class)->create(
            $user,
            $this->feature(),
            'Tenant B CLI',
            ['customers:read'],
        );

        $resolver->tenant = $tenantA;

        $this->withToken($tokenA->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertOk()
            ->assertJson([
                'token_id' => (string) $tokenA->accessToken->getKey(),
                'tenant_id' => (string) $tenantA->getKey(),
            ]);

        $this->withToken($tokenB->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertForbidden();

        $resolver->tenant = $tenantB;

        $this->withToken($tokenB->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertOk()
            ->assertJson([
                'token_id' => (string) $tokenB->accessToken->getKey(),
                'tenant_id' => (string) $tenantB->getKey(),
            ]);

        $this->withToken($tokenA->plainTextToken)
            ->getJson('/__test/tenant-token')
            ->assertForbidden();
    }

    public function test_token_context_migration_is_shipped_as_opt_in_stub(): void
    {
        self::assertFileExists(__DIR__.'/../../database/migrations/add_context_columns_to_personal_access_tokens.php.stub');
    }

    protected function registerCustomTenancyPanel(MutableTenancyResolver $resolver): void
    {
        $panel = Panel::make()
            ->id('http-tenancy')
            ->plugin(
                CompleteUserProfilePlugin::make()
                    ->tenancyResolver($resolver),
            );

        Filament::setCurrentPanel($panel);
        Filament::setTenant(null, isQuiet: true);
    }

    protected function registerTenantProtectedRoute(): void
    {
        Route::middleware([
            'auth:sanctum',
            EnsureTokenContext::class,
        ])->get('/__test/tenant-token', function (Request $request) {
            $user = $request->user();
            $token = is_object($user) && is_callable([$user, 'currentAccessToken'])
                ? $user->currentAccessToken()
                : null;
            $tenant = app(\Mortalkiller\FilamentCompleteUserProfile\Tenancy\TenancyManager::class)->resolve();

            return response()->json([
                'token_id' => $token instanceof Model ? (string) $token->getKey() : null,
                'tenant_id' => $tenant instanceof Model ? (string) $tenant->getKey() : null,
            ]);
        });
    }

    protected function feature(): ApiTokens
    {
        return ApiTokens::make()
            ->enabled()
            ->tenantScoped()
            ->abilities(['customers:read' => 'Read customers']);
    }
}
