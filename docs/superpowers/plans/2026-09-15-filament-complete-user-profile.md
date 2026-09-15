# Filament Complete User Profile Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a production-ready Filament 5 account center package with profile editing, password management, optional native MFA, optional database-session management, and optional Sanctum API tokens with tenant/context isolation.

**Architecture:** Register one normal Filament profile page and compose it from focused feature objects. Keep framework-owned concerns in Laravel/Filament (authentication, TOTP, sessions and Sanctum), while package-specific storage, reauthentication, session access and token context are isolated behind contracts.

**Tech Stack:** PHP `^8.3`, Laravel `^13.0`, Filament `^5.7`, Livewire 4 through Filament, PHPUnit 12, Orchestra Testbench 11, Laravel Sanctum `^4.3` as an optional runtime dependency/dev dependency, SQLite for package tests.

**Spec:** `docs/superpowers/specs/2026-09-15-filament-complete-user-profile-design.md`

## Global Constraints

- PHP `^8.3`, Laravel `^13.0`.
- Require Filament `^5.7`; do not allow earlier Filament 5 releases because MFA recovery-code bypasses were patched in 5.7.0.
- Use native Laravel 13 / Filament 5 APIs whenever they already provide the primitive.
- Defaults: Overview ON, Profile ON, Avatar ON, Name ON, Email ON, Locale ON, Password ON; MFA OFF, Sessions OFF, API Tokens OFF.
- Feature enablement lives in the plugin API. Structural storage configuration lives in package config.
- Application-owned tables must be altered defensively with `Schema::hasColumn()` and package rollbacks must not delete pre-existing application columns.
- Sessions v1 supports Laravel database sessions only.
- API token authentication uses Sanctum. Never grant `*` implicitly.
- Tenant-scoped tokens fail closed if context cannot be resolved.
- Code identifiers, comments, errors, validation messages and command output are English.
- Out of scope: theme color, generic delete-account flow, connected social accounts, IP geolocation, timezone defaults, generic JSON custom fields, custom TOTP, custom token authentication engine, shadow session storage.

---

## Target File Structure

```text
config/
└── filament-complete-user-profile.php

database/migrations/
├── add_complete_user_profile_columns.php.stub
├── create_filament_user_profiles_table.php.stub
└── add_context_columns_to_personal_access_tokens.php.stub

resources/
├── lang/en/profile.php
└── views/pages/complete-user-profile.blade.php

src/
├── Commands/CheckCompleteUserProfile.php
├── Concerns/InteractsWithMultiFactorAuthentication.php
├── Contracts/
│   ├── ProfileFeature.php
│   ├── ProfileStorage.php
│   ├── Reauthentication.php
│   ├── SessionStore.php
│   └── TokenContextResolver.php
├── Features/
│   ├── AbstractFeature.php
│   ├── ApiTokens.php
│   ├── Overview.php
│   ├── Profile.php
│   ├── Security.php
│   └── Sessions.php
├── Http/Middleware/
│   ├── EnsureTokenContext.php
│   └── SetUserLocale.php
├── Models/StoredProfile.php
├── Pages/CompleteUserProfile.php
├── Security/PasswordReauthentication.php
├── Sessions/
│   ├── DatabaseSessionStore.php
│   └── SessionData.php
├── Storage/
│   ├── SeparateProfileStorage.php
│   └── UserProfileStorage.php
├── Support/
│   ├── ProfileColumnMap.php
│   └── UserModelResolver.php
├── Tokens/
│   ├── TokenContext.php
│   └── TokenManager.php
├── CompleteUserProfilePlugin.php
└── CompleteUserProfileServiceProvider.php

tests/
├── Feature/
│   ├── ApiTokensTest.php
│   ├── CheckCommandTest.php
│   ├── CompleteUserProfilePageTest.php
│   ├── MfaTest.php
│   ├── ProfileTest.php
│   ├── SecurityTest.php
│   ├── SessionsTest.php
│   └── TenantScopedTokensTest.php
├── Fixtures/
│   ├── Tenant.php
│   └── User.php
├── Unit/
│   ├── PackageBootTest.php
│   ├── PluginConfigurationTest.php
│   ├── ProfileStorageTest.php
│   └── UserModelResolverTest.php
└── TestCase.php
```

---

### Task 1: Scaffold the package and test harness

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `.gitignore`
- Create: `src/CompleteUserProfileServiceProvider.php`
- Create: `tests/TestCase.php`
- Create: `tests/Fixtures/User.php`
- Create: `tests/Unit/PackageBootTest.php`

**Interfaces:**
- Composer package: `mortalkiller/filament-complete-user-profile`
- Namespace: `Mortalkiller\FilamentCompleteUserProfile\`
- Auto-discovered provider: `CompleteUserProfileServiceProvider`

- [ ] **Step 1: Write the failing package boot test**

```php
public function test_service_provider_is_loaded(): void
{
    self::assertArrayHasKey(
        CompleteUserProfileServiceProvider::class,
        $this->app->getLoadedProviders(),
    );
}
```

- [ ] **Step 2: Create `composer.json` with exact baseline dependencies**

```json
{
    "name": "mortalkiller/filament-complete-user-profile",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.3",
        "filament/filament": "^5.7",
        "illuminate/contracts": "^13.0",
        "illuminate/database": "^13.0",
        "illuminate/support": "^13.0"
    },
    "require-dev": {
        "laravel/pint": "^1.27",
        "laravel/sanctum": "^4.3",
        "orchestra/testbench": "^11.2",
        "phpstan/phpstan": "^2.2",
        "phpunit/phpunit": "^12.5"
    },
    "suggest": {
        "laravel/sanctum": "Required only when the API Tokens feature is enabled."
    },
    "autoload": {
        "psr-4": {
            "Mortalkiller\\FilamentCompleteUserProfile\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Mortalkiller\\FilamentCompleteUserProfile\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Mortalkiller\\FilamentCompleteUserProfile\\CompleteUserProfileServiceProvider"
            ]
        }
    }
}
```

- [ ] **Step 3: Build Testbench base with in-memory SQLite and package provider**

```php
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [CompleteUserProfileServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
```

- [ ] **Step 4: Run focused test, then the base quality suite**

```bash
composer install
vendor/bin/phpunit tests/Unit/PackageBootTest.php
vendor/bin/pint --test
vendor/bin/phpstan analyse src tests --level=8
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock phpunit.xml.dist .gitignore src tests
git commit -m "chore: scaffold complete user profile package"
```

---

### Task 2: Implement feature objects and the fluent plugin API

**Files:**
- Create: `src/Contracts/ProfileFeature.php`
- Create: `src/Features/AbstractFeature.php`
- Create: `src/Features/{Overview,Profile,Security,Sessions,ApiTokens}.php`
- Create: `src/CompleteUserProfilePlugin.php`
- Create: `tests/Unit/PluginConfigurationTest.php`

**Interfaces:**

```php
interface ProfileFeature
{
    public function getId(): string;
    public function isEnabled(): bool;
    public function isVisible(): bool;
    public function getSort(): int;
}
```

Public feature methods follow one rule: a boolean toggles the feature; a Closure configures the feature object. Runtime conditional display belongs to `visible()`.

- [ ] **Step 1: Test defaults**

```php
$plugin = CompleteUserProfilePlugin::make();

self::assertTrue($plugin->getFeature('overview')->isEnabled());
self::assertTrue($plugin->getFeature('profile')->isEnabled());
self::assertTrue($plugin->getFeature('security')->isEnabled());
self::assertFalse($plugin->getFeature('sessions')->isEnabled());
self::assertFalse($plugin->getFeature('api-tokens')->isEnabled());
self::assertFalse($plugin->getFeature('security')->hasMultiFactorAuthentication());
```

- [ ] **Step 2: Test fluent configuration**

```php
$plugin = CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile) => $profile->avatar(false))
    ->sessions()
    ->apiTokens(fn (ApiTokens $tokens) => $tokens->abilities([
        'customers:read' => 'Read customers',
    ]));
```

Assert `profile(false)`, `sessions(false)`, `apiTokens(false)` work and that `visible(fn () => false)` hides a feature without disabling it.

- [ ] **Step 3: Implement the feature registry**

Default feature instances:

```php
$this->features = [
    'overview' => Overview::make()->enabled(),
    'profile' => Profile::make()->enabled(),
    'security' => Security::make()->enabled()->password(),
    'sessions' => Sessions::make()->enabled(false),
    'api-tokens' => ApiTokens::make()->enabled(false),
];
```

- [ ] **Step 4: Add shortcut `multiFactorAuthentication()`**

It must delegate to `Security::multiFactorAuthentication()` and not create a second MFA configuration source.

- [ ] **Step 5: Run and commit**

```bash
vendor/bin/phpunit tests/Unit/PluginConfigurationTest.php
git add src tests/Unit/PluginConfigurationTest.php
git commit -m "feat: add composable profile feature registry"
```

---

### Task 3: Add structural config, storage and defensive migrations

**Files:**
- Create: `config/filament-complete-user-profile.php`
- Create: `src/Contracts/ProfileStorage.php`
- Create: `src/Support/UserModelResolver.php`
- Create: `src/Support/ProfileColumnMap.php`
- Create: `src/Storage/UserProfileStorage.php`
- Create: `src/Storage/SeparateProfileStorage.php`
- Create: `src/Models/StoredProfile.php`
- Create: `database/migrations/add_complete_user_profile_columns.php.stub`
- Create: `database/migrations/create_filament_user_profiles_table.php.stub`
- Modify: `src/CompleteUserProfileServiceProvider.php`
- Create: `tests/Unit/UserModelResolverTest.php`
- Create: `tests/Unit/ProfileStorageTest.php`

**Interfaces:**

```php
interface ProfileStorage
{
    public function get(Authenticatable $user, string $key): mixed;
    public function put(Authenticatable $user, string $key, mixed $value): void;
    public function putMany(Authenticatable $user, array $values): void;
}
```

Default config:

```php
return [
    'user_model' => null,
    'storage' => 'user',
    'columns' => [
        'avatar' => 'avatar_url',
        'locale' => 'locale',
        'mfa' => [
            'secret' => 'app_authentication_secret',
            'recovery_codes' => 'app_authentication_recovery_codes',
        ],
    ],
    'profile_table' => 'filament_user_profiles',
];
```

- [ ] **Step 1: Test user model resolution and custom column names**

Resolve configured `user_model` first; otherwise resolve the default auth provider model. Test `preferred_locale` and `avatar` custom column mappings.

- [ ] **Step 2: Test both storage drivers**

`user` writes package-managed values to configured User columns. `separate` writes them to one polymorphic `StoredProfile` record per authenticatable.

- [ ] **Step 3: Implement defensive migrations**

Every application-column addition follows:

```php
if (! Schema::hasColumn($tableName, $columnName)) {
    Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
        $table->text($columnName)->nullable();
    });
}
```

Do not remove application-owned fields in `down()`.

The separate-storage table is package-owned and may be dropped normally. `StoredProfile` casts MFA secret as `encrypted` and recovery codes as `encrypted:array`.

- [ ] **Step 4: Treat storage mode as structural**

`storage = separate` must be chosen before the first package migration. Switching storage later requires an application data migration; do not attempt an automatic live migration between drivers.

- [ ] **Step 5: Run and commit**

```bash
vendor/bin/phpunit tests/Unit/UserModelResolverTest.php tests/Unit/ProfileStorageTest.php
git add config database src tests
git commit -m "feat: add configurable profile storage"
```

---

### Task 4: Register the normal Filament profile page and account navigation

**Files:**
- Create: `src/Pages/CompleteUserProfile.php`
- Create: `resources/views/pages/complete-user-profile.blade.php`
- Modify: `src/CompleteUserProfilePlugin.php`
- Create: `tests/Feature/CompleteUserProfilePageTest.php`

**Interfaces:**
- `CompleteUserProfile extends Filament\Auth\Pages\EditProfile`
- `CompleteUserProfile::getVisibleFeatures(): array`
- Stable section IDs: `overview`, `profile`, `security`, `sessions`, `api-tokens`

- [ ] **Step 1: Test that the page is registered as a normal page**

The plugin must configure:

```php
$panel->profile(
    CompleteUserProfile::class,
    isSimple: false,
);
```

- [ ] **Step 2: Test visible feature navigation**

Default navigation contains Overview, Profile, Security. Sessions and API Tokens only appear when enabled and visible.

- [ ] **Step 3: Implement responsive internal navigation**

Desktop: approximately 220–240px secondary navigation on the left and flexible content on the right. Mobile/tablet: scrollable internal navigation at the top. Use Filament theme tokens/components; do not introduce package-specific colors.

- [ ] **Step 4: Preserve native Filament user-menu profile behavior**

Do not create a second independent profile route when Filament's profile slot can point to this page.

- [ ] **Step 5: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/CompleteUserProfilePageTest.php
git add src resources tests/Feature/CompleteUserProfilePageTest.php
git commit -m "feat: register complete profile page"
```

---

### Task 5: Implement Profile defaults, avatar, locale and custom fields

**Files:**
- Modify: `src/Features/Profile.php`
- Modify: `src/Pages/CompleteUserProfile.php`
- Create: `src/Http/Middleware/SetUserLocale.php`
- Create: `resources/lang/en/profile.php`
- Create: `tests/Feature/ProfileTest.php`

**Interfaces:**
- `avatar(bool|Closure $value = true)`
- `name(bool|Closure $value = true)`
- `email(bool|Closure $value = true)`
- `locale(bool|array|Closure $value = true)`
- `fields(array|Closure $fields)`
- `modifyFieldsUsing(Closure $callback)`
- `mutateDataBeforeSaveUsing(Closure $callback)`
- `afterSave(Closure $callback)`

- [ ] **Step 1: Test default fields and locale resolution**

Assert Avatar, Name, Email and Locale render by default. Locale priority is explicit options, then `app.supported_locales`, then a single `app.locale` fallback.

- [ ] **Step 2: Reuse native name/email components**

```php
public function getNameFormComponent(): Component
{
    return parent::getNameFormComponent();
}

public function getEmailFormComponent(): Component
{
    return parent::getEmailFormComponent();
}
```

Preserve Filament email-change verification behavior.

- [ ] **Step 3: Implement Avatar and Locale with native components**

Use `FileUpload` and `Select`. The default `avatar_url` works with Filament without a trait. If a consumer uses a different avatar column or separate storage, document that Filament's `HasAvatar` contract is required for the panel chrome to use that custom source.

- [ ] **Step 4: Implement additive field customization and persistence**

`fields()` appends; it never replaces defaults. `modifyFieldsUsing()` runs after defaults + appended fields are assembled. Normal model-backed custom fields persist through the User model; package-managed avatar/locale use `ProfileStorage` when storage is separate.

- [ ] **Step 5: Apply saved locale on authenticated panel requests**

`SetUserLocale` reads Locale through `ProfileStorage` and calls `App::setLocale()` only when a non-empty value exists.

- [ ] **Step 6: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/ProfileTest.php
git add src resources/lang tests/Feature/ProfileTest.php
git commit -m "feat: add extensible profile fields"
```

---

### Task 6: Implement Overview, Password and shared reauthentication

**Files:**
- Modify: `src/Features/Overview.php`
- Modify: `src/Features/Security.php`
- Create: `src/Contracts/Reauthentication.php`
- Create: `src/Security/PasswordReauthentication.php`
- Modify: `src/CompleteUserProfileServiceProvider.php`
- Create: `tests/Feature/SecurityTest.php`

**Interfaces:**

```php
interface Reauthentication
{
    public function isAvailable(Authenticatable $user): bool;

    /** @return array<Component> */
    public function getFormSchema(): array;

    public function confirm(Authenticatable $user, array $data): void;
}
```

- [ ] **Step 1: Test password flow**

Use the active Filament guard, Laravel `Password::default()`, current-password validation and secure hashing. Test successful update and wrong-current-password failure.

- [ ] **Step 2: Fail closed for passwordless users**

`PasswordReauthentication::isAvailable()` returns false when the user has no usable local password. Do not show a fake current-password flow and do not silently bypass confirmation. Sensitive actions become unavailable until the application binds a custom `Reauthentication` provider.

- [ ] **Step 3: Bind default reauthentication through the container**

Allow applications using Google/Socialite/passwordless auth to replace the implementation in their own service provider without changing Sessions/Tokens code.

- [ ] **Step 4: Build Overview from enabled capabilities**

Always show existing avatar/name/email/locale when available. Show MFA state only when MFA is enabled, session count only when Sessions is enabled and usable, and token count only in the currently valid token context.

- [ ] **Step 5: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/SecurityTest.php tests/Feature/CompleteUserProfilePageTest.php
git add src tests
git commit -m "feat: add account overview and password security"
```

---

### Task 7: Integrate native Filament MFA and recovery codes

**Files:**
- Create: `src/Contracts/HasMultiFactorAuthentication.php`
- Create: `src/Concerns/InteractsWithMultiFactorAuthentication.php`
- Modify: `src/Features/Security.php`
- Modify: `src/CompleteUserProfilePlugin.php`
- Create: `tests/Feature/MfaTest.php`

**Interfaces:**

```php
interface HasMultiFactorAuthentication extends HasAppAuthentication, HasAppAuthenticationRecovery
{
}
```

- [ ] **Step 1: Test MFA is OFF by default and native when enabled**

Enable through either:

```php
CompleteUserProfilePlugin::make()->multiFactorAuthentication();
```

or:

```php
->security(fn (Security $security) => $security->multiFactorAuthentication());
```

Assert Filament receives `AppAuthentication::make()->recoverable()`.

- [ ] **Step 2: Implement the thin storage-aware model trait**

The trait implements only the Filament MFA storage contracts through `ProfileStorage`:

```php
public function getAppAuthenticationSecret(): ?string
{
    return app(ProfileStorage::class)->get($this, 'mfa.secret');
}

public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
{
    app(ProfileStorage::class)->put($this, 'mfa.secret', $secret);
}

public function getAppAuthenticationHolderName(): string
{
    return (string) $this->getAttribute('email');
}

public function getAppAuthenticationRecoveryCodes(): ?array
{
    return app(ProfileStorage::class)->get($this, 'mfa.recovery_codes');
}

public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
{
    app(ProfileStorage::class)->put($this, 'mfa.recovery_codes', $codes);
}
```

For user storage, dynamically add encrypted/hidden casts for the configured MFA columns. For separate storage, rely on `StoredProfile` encrypted casts.

- [ ] **Step 3: Keep all TOTP behavior inside Filament**

Do not implement QR generation, TOTP generation/verification, challenge logic, recovery-code generation or login flow. Render the management schema returned by Filament's registered MFA provider.

- [ ] **Step 4: Test missing model contracts clearly**

The feature/diagnostics must report that the authenticatable model does not implement the package/Filament MFA contract rather than allowing a deep framework exception.

- [ ] **Step 5: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/MfaTest.php
git add src tests/Feature/MfaTest.php
git commit -m "feat: integrate native filament mfa"
```

---

### Task 8: Implement database browser-session management

**Files:**
- Create: `src/Contracts/SessionStore.php`
- Create: `src/Sessions/SessionData.php`
- Create: `src/Sessions/DatabaseSessionStore.php`
- Modify: `src/Features/Sessions.php`
- Modify: `src/CompleteUserProfileServiceProvider.php`
- Create: `tests/Feature/SessionsTest.php`

**Interfaces:**

```php
interface SessionStore
{
    public function sessionsFor(Authenticatable $user): Collection;
    public function revoke(Authenticatable $user, string $sessionId): void;
    public function revokeOthers(Authenticatable $user, string $currentSessionId): void;
}
```

- [ ] **Step 1: Write security-first tests**

Seed sessions for two users. Assert only own sessions are listed, current session is marked, current session cannot be revoked, another user's session ID cannot be revoked, and bulk revocation preserves current session.

- [ ] **Step 2: Implement scoped database access**

Every deletion contains both:

```php
->where('user_id', $user->getAuthIdentifier())
->where('id', $sessionId)
```

Use `session.connection` and `session.table` configuration. Do not create a package session table.

- [ ] **Step 3: Build Filament table/actions**

Columns: Device, IP, Last activity, Status. Device combines browser/platform/device type from user-agent. No IP geolocation. Current row has no Revoke action.

- [ ] **Step 4: Use shared reauthentication for `Revoke other sessions`**

The feature must know only the `Reauthentication` contract, not passwords.

- [ ] **Step 5: Unsupported drivers fail gracefully**

If Sessions is enabled and driver is not `database`, boot still succeeds. Feature is unavailable; diagnostics fail it; local/dev may show a concise callout.

- [ ] **Step 6: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/SessionsTest.php
git add src tests/Feature/SessionsTest.php
git commit -m "feat: add browser session management"
```

---

### Task 9: Implement user-scoped Sanctum API tokens

**Files:**
- Modify: `src/Features/ApiTokens.php`
- Create: `src/Tokens/TokenManager.php`
- Create: `tests/Feature/ApiTokensTest.php`

**Interfaces:**
- `abilities(array $abilities)`
- `defaultExpiration(?int $days)`
- `maxExpiration(?int $days)`
- `TokenManager::create(...)`
- `TokenManager::revoke(...)`

- [ ] **Step 1: Test safe defaults**

API Tokens is OFF by default. Creation is blocked if ability whitelist is empty. Submitted abilities must exist in the whitelist. `*` is never inserted automatically.

- [ ] **Step 2: Keep Sanctum optional at runtime**

Before using the feature, check `class_exists(Laravel\Sanctum\Sanctum::class)`. If absent, return a capability/configuration failure rather than loading Sanctum classes eagerly.

- [ ] **Step 3: Create and revoke through Sanctum public APIs**

```php
$newToken = $user->createToken(
    $name,
    array_values($abilities),
    $expiresAt,
);
```

Revoke through `$user->tokens()` already scoped to the authenticated user.

- [ ] **Step 4: Build native Filament table and creation modal**

Columns: Name, Permissions, Last used, Expires, Actions. Creation: required name, checkbox-list abilities, optional expiration.

- [ ] **Step 5: Show plaintext only once**

Immediately after creation, show `plainTextToken` in a second modal that cannot close by clicking outside. Never attempt to reconstruct/read plaintext later.

- [ ] **Step 6: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/ApiTokensTest.php
git add src tests/Feature/ApiTokensTest.php
git commit -m "feat: add sanctum api token management"
```

---

### Task 10: Add tenant/context isolation for tokens

**Files:**
- Create: `src/Contracts/TokenContextResolver.php`
- Create: `src/Tokens/TokenContext.php`
- Create: `src/Http/Middleware/EnsureTokenContext.php`
- Create: `database/migrations/add_context_columns_to_personal_access_tokens.php.stub`
- Modify: `src/Features/ApiTokens.php`
- Modify: `src/Tokens/TokenManager.php`
- Modify: `src/CompleteUserProfileServiceProvider.php`
- Create: `tests/Fixtures/Tenant.php`
- Create: `tests/Feature/TenantScopedTokensTest.php`

**Interfaces:**

```php
interface TokenContextResolver
{
    public function resolve(): ?Model;
}
```

Public API:

```php
->apiTokens(fn (ApiTokens $tokens) => $tokens
    ->tenantScoped()
    ->abilities([
        'customers:read' => 'Read customers',
    ]));
```

- [ ] **Step 1: Test context persistence and fail-closed behavior**

Tenant A token stores Tenant A type/key. No active context blocks token creation. Tenant A token + Tenant B API context returns 403. Tenant A UI does not list/count/revoke Tenant B tokens owned by the same user.

- [ ] **Step 2: Make the token-context migration opt-in**

Do not auto-load it because Sanctum is optional. Publish under:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-token-migrations
php artisan migrate
```

The migration adds `context_type` and `context_id` only when missing. Diagnostics must fail `tenantScoped()` until both fields exist.

- [ ] **Step 3: Resolve management context from Filament**

For Filament management use `Filament::getTenant()`. Every tenant-scoped list/count/create/revoke query filters by authenticated user and active context.

- [ ] **Step 4: Resolve API request context through the container**

Applications bind `TokenContextResolver`. No hard dependency on Tenancy v3, Stancl, Spatie Teams or another tenancy library.

- [ ] **Step 5: Enforce context after `auth:sanctum`**

`EnsureTokenContext` compares the current access token's stored morph class/key with the resolver result. Missing/mismatched context returns 403 with an English message.

- [ ] **Step 6: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/TenantScopedTokensTest.php
git add database src tests/Feature/TenantScopedTokensTest.php tests/Fixtures/Tenant.php
git commit -m "feat: scope api tokens to tenant context"
```

---

### Task 11: Add installation diagnostics

**Files:**
- Create: `src/Commands/CheckCompleteUserProfile.php`
- Modify: `src/CompleteUserProfileServiceProvider.php`
- Create: `tests/Feature/CheckCommandTest.php`

**Interface:** `php artisan filament-complete-user-profile:check`

- [ ] **Step 1: Test enabled-feature-aware checks**

Default: User model, Profile storage, Avatar column and Locale column PASS. MFA without model contract FAILS. Sessions with non-database driver FAILS. Tokens without Sanctum/HasApiTokens FAIL. Tenant-scoped tokens without context columns/resolver FAIL. Disabled optional features do not fail the command.

- [ ] **Step 2: Inspect registered Filament panels**

Check plugin instances on registered panels. If the plugin is absent from every panel, return FAIL with a registration message.

- [ ] **Step 3: Use consistent statuses**

```text
PASS
FAIL
INFO
```

Exit 0 only when all requirements of enabled features pass; exit 1 otherwise.

- [ ] **Step 4: Keep messages actionable**

Examples must state exactly what is missing, such as `SESSION_DRIVER=database`, missing MFA contract, missing Sanctum trait, missing token-context migration or missing `TokenContextResolver` binding.

- [ ] **Step 5: Run and commit**

```bash
vendor/bin/phpunit tests/Feature/CheckCommandTest.php
php vendor/bin/testbench filament-complete-user-profile:check
git add src/Commands src/CompleteUserProfileServiceProvider.php tests/Feature/CheckCommandTest.php
git commit -m "feat: add package diagnostics command"
```

---

### Task 12: Finish README, translations, CI and release verification

**Files:**
- Create: `README.md`
- Create: `LICENSE.md`
- Create: `.github/workflows/tests.yml`
- Create: `pint.json`
- Create: `phpstan.neon.dist`
- Modify: `resources/lang/en/profile.php`
- Modify: `composer.json`

- [ ] **Step 1: README starts with the zero-config path**

```php
CompleteUserProfilePlugin::make()
```

State clearly that this already enables Overview, Avatar, Name, Email, Locale and Password.

README order:

```text
Installation
Default experience
Enable MFA
Enable Sessions
Enable API Tokens
Tenant-scoped tokens
Customize Profile fields
Structural config
Diagnostics
Advanced contracts
```

- [ ] **Step 2: Document prerequisites exactly**

MFA: convenience contract/trait on User. Sessions: `SESSION_DRIVER=database`. Tokens: install Sanctum and use `HasApiTokens`. Tenant tokens: publish token-context migration, bind `TokenContextResolver`, apply context middleware after `auth:sanctum`.

- [ ] **Step 3: Add CI for supported PHP versions**

Matrix: PHP 8.3, 8.4, 8.5. Run:

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse src tests --level=8
vendor/bin/phpunit
```

- [ ] **Step 4: Audit public API against the approved design**

The following must work without `shouldShow...()` methods:

```php
CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile) => $profile
        ->avatar()
        ->locale([
            'pt' => 'Português',
            'en' => 'English',
        ]))
    ->security(fn (Security $security) => $security
        ->password()
        ->multiFactorAuthentication())
    ->sessions()
    ->apiTokens(fn (ApiTokens $tokens) => $tokens
        ->tenantScoped()
        ->abilities([
            'customers:read' => 'Read customers',
            'customers:write' => 'Manage customers',
        ]));
```

- [ ] **Step 5: Run final verification**

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse src tests --level=8
vendor/bin/phpunit
```

Verify additionally:
- zero-config plugin shows only default features;
- every optional feature works independently;
- tenant tokens never fall back to global context;
- package rollback never drops application-owned columns;
- package contains no custom TOTP implementation, session lifecycle or token authentication engine.

- [ ] **Step 6: Commit**

```bash
git add README.md LICENSE.md .github pint.json phpstan.neon.dist resources/lang composer.json
git commit -m "docs: complete package installation and quality setup"
```

---

## Implementation Order Rationale

Tasks 1–4 establish package boundaries, storage and page composition first. Tasks 5–6 implement the default experience. Tasks 7–10 add isolated opt-in security capabilities only after the core is stable. Task 11 makes invalid installations diagnosable. Task 12 documents and verifies the actual implemented contracts instead of anticipated behavior.
