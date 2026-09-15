# Filament Complete User Profile — Design Specification

## Goal

Build a Laravel-style, Filament-native account center for Filament 5 that provides profile management, password management, optional MFA, optional browser sessions, and optional Sanctum API tokens through a small, composable API.

The package must reuse native Laravel 13 and Filament 5 capabilities whenever they already solve the problem. The package owns composition, UX, configuration, validation, and safe integration rather than reimplementing authentication or token primitives.

## Platform

- Laravel 13
- Filament 5
- PHP compatible with both
- Sanctum only when API tokens are enabled
- Database sessions only when session management is enabled

Optional features must not make their dependencies mandatory when disabled.

## Principles

1. Zero-configuration installation for the common case.
2. Public API should read like Laravel / Filament code.
3. Prefer native Filament and Laravel components.
4. Features must be independently enabled or disabled.
5. Never silently weaken authentication or tenant isolation.
6. Migrations touching application-owned tables must be defensive.
7. Package-owned tables may migrate and roll back normally.
8. Normal customization must not require publishing views.
9. No hard dependency on tenancy packages or social-auth providers.
10. Keep advanced extension points out of the way of the default API.

## Installation

```bash
composer require mortalkiller/filament-complete-user-profile
php artisan migrate
```

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;

$panel->plugins([
    CompleteUserProfilePlugin::make(),
]);
```

No config, migrations, or views must need to be published for the default case.

## Defaults

| Feature | Default |
| --- | --- |
| Overview | ON |
| Profile | ON |
| Avatar | ON |
| Name | ON |
| Email | ON |
| Locale | ON |
| Password | ON |
| MFA | OFF |
| Sessions | OFF |
| API Tokens | OFF |

## Core architecture

Use Filament's native profile page as the base and register it as a normal panel page:

```php
$panel->profile(
    CompleteUserProfile::class,
    isSimple: false,
);
```

Initial features:

```text
Overview
Profile
Security
Sessions
ApiTokens
```

Features should share a common contract so future package versions or third parties can add account sections without growing one giant plugin class.

Convenience API remains the normal API:

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
        ->abilities([
            'customers:read' => 'Read customers',
            'customers:write' => 'Manage customers',
        ]));
```

Explicit disabling:

```php
CompleteUserProfilePlugin::make()
    ->overview(false)
    ->profile(false)
    ->security(false)
    ->sessions(false)
    ->apiTokens(false);
```

Runtime visibility belongs to the feature:

```php
->apiTokens(fn (ApiTokens $tokens) => $tokens
    ->visible(fn (): bool => Filament::getTenant() !== null));
```

## Config responsibilities

The config file is only for infrastructure/storage decisions. Feature enablement belongs exclusively to the plugin API.

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

### `user_model`

`null` means resolve the authenticatable model from the panel/application auth provider.

Custom applications may set:

```php
'user_model' => App\Models\Admin::class,
```

The table should be derived from the model.

### `storage`

Supported values:

```text
user
separate
```

`user` is the default and stores package-managed profile fields directly on the authenticatable table.

`separate` is the advanced option for applications that do not want package-managed fields on their authenticatable table.

### Column names

Names are configurable so existing applications can reuse fields such as:

```text
avatar
preferred_locale
```

The package must reuse configured existing columns rather than duplicating them.

## Migration rules

Application-owned tables must always be inspected before alteration:

```php
if (! Schema::hasColumn($tableName, $column)) {
    // Add the column.
}
```

This applies to avatar, locale and MFA fields.

### Rollback rule

Do not destructively drop application-owned columns on rollback unless ownership can be proven reliably.

Package-owned tables may be dropped normally.

### Separate storage

When `storage = separate`, the package owns a table conceptually containing:

```text
id
user_type
user_id
avatar_url
locale
app_authentication_secret
app_authentication_recovery_codes
created_at
updated_at
```

The schema must safely support different authenticatable models.

## Page UX

The account page uses the normal Filament panel chrome.

Desktop:
- Filament sidebar/topbar remain visible.
- Internal account navigation on the left.
- Content on the right.

Initial navigation:

```text
Overview
Profile
Security
Sessions
API Tokens
```

Disabled features are hidden.

Mobile/tablet:
- Internal navigation becomes responsive top tabs/navigation.
- No global horizontal scroll.

Use native Filament components and theme styles wherever possible.

## Overview

Enabled by default.

May display:
- Avatar
- Name
- Email
- Existing app role/membership badges via extension points
- Preferred locale
- MFA status when relevant
- Active session count when Sessions is enabled
- API token count when tokens are enabled in the current context

Do not invent location, timezone, theme color or other missing data.

Tenant-scoped token information must never fall back to a global context.

## Profile

Enabled by default.

Default fields:

```text
Avatar
Name
Email
Locale
```

Reuse native Filament profile behavior for name/email whenever possible, including email-change verification behavior.

### Avatar

Enabled by default.

```php
->profile(fn (Profile $profile) => $profile
    ->avatar(false));
```

Advanced customization:

```php
->profile(fn (Profile $profile) => $profile
    ->avatar(fn (FileUpload $field) => $field
        ->maxSize(2048)));
```

### Name and email

Enabled by default.

Advanced field customization:

```php
->name(fn (TextInput $field) => $field->maxLength(100))
->email(fn (TextInput $field) => $field->helperText('Used for notifications.'))
```

### Locale

Enabled by default.

Resolution order:

1. Options explicitly configured on the feature.
2. `config('app.supported_locales')` when available.
3. `config('app.locale')` as a one-option fallback.

Example:

```php
->locale([
    'pt' => 'Português',
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
]);
```

### Custom fields

Primary extension API:

```php
->profile(fn (Profile $profile) => $profile
    ->fields([
        TextInput::make('phone')->tel(),
        TextInput::make('job_title'),
    ]));
```

`fields()` appends fields; it does not replace defaults.

Advanced transformation:

```php
->profile(fn (Profile $profile) => $profile
    ->modifyFieldsUsing(function (array $fields): array {
        return $fields;
    }));
```

Custom Eloquent-backed fields should persist through the normal save flow.

Possible advanced hooks:

```php
->mutateDataBeforeSaveUsing(...)
->afterSave(...)
```

Do not introduce a generic `custom_fields` JSON column as the default mechanism.

## Security

Security is present by default because Password is enabled by default.

Capabilities:

```text
Password
MFA
```

### Password

Enabled by default.

Use the active Filament guard plus native Laravel password validation/hashing.

Sensitive operations in other modules must not each implement their own password checks.

### Passwordless/social-only users

Do not assume every authenticated user knows a local password.

A shared reauthentication abstraction must support the default password flow while allowing applications with passwordless/external authentication to provide a custom secure strategy.

Google/Socialite integration itself is outside package scope.

## MFA

Disabled by default.

Enable via:

```php
CompleteUserProfilePlugin::make()
    ->multiFactorAuthentication();
```

or:

```php
->security(fn (Security $security) => $security
    ->multiFactorAuthentication());
```

Use Filament's native app-authentication/TOTP implementation and native management schema. Do not implement TOTP.

Recovery codes are ON by default when MFA is enabled.

### MFA model integration

The authenticatable model must satisfy Filament's native MFA contracts.

The package may provide a thin convenience contract/trait:

```php
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication;

class User extends Authenticatable implements HasMultiFactorAuthentication
{
    use InteractsWithMultiFactorAuthentication;
}
```

These wrappers delegate to Filament-native behavior.

The diagnostic command must detect missing integration and explain it clearly.

### External authentication

Document explicitly that arbitrary external login flows must not bypass Filament's MFA challenge.

Provider-specific Socialite code is out of scope.

## Sessions

Disabled by default.

```php
CompleteUserProfilePlugin::make()
    ->sessions();
```

v1 officially supports Laravel database sessions only.

Read Laravel's own config:

```php
config('session.driver')
config('session.connection')
config('session.table')
```

Do not duplicate those settings in package config.

### UI

Use Filament Table/actions/notifications.

Show:
- Browser
- OS/platform
- Device type
- IP
- Last activity
- Current-session state

Do not add IP geolocation.

Actions:
- Revoke one non-current session
- Revoke all other sessions

Current session can never be revoked from this UI.

### Security

Every lookup/delete must scope by the authenticated user's identifier and session ID.

Never delete by untrusted session ID alone.

Bulk revocation uses shared reauthentication.

### Internal abstraction

```php
interface SessionStore
{
    public function sessionsFor(Authenticatable $user): Collection;

    public function revoke(
        Authenticatable $user,
        string $sessionId,
    ): void;

    public function revokeOthers(
        Authenticatable $user,
        string $currentSessionId,
    ): void;
}
```

Initial implementation:

```text
DatabaseSessionStore
```

Do not create a shadow sessions table for unsupported drivers.

### Unsupported drivers

If Sessions is enabled with an unsupported driver:
- Do not crash application boot.
- Diagnostic command reports failure.
- Feature becomes unavailable.
- Local/dev may show a Filament callout.
- Production may log a concise warning.

## API Tokens

Disabled by default.

Authentication engine: Laravel Sanctum.

```php
CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens) => $tokens
        ->abilities([
            'customers:read' => 'Read customers',
            'customers:write' => 'Manage customers',
        ]));
```

### Abilities

Abilities are an application-owned whitelist.

End users never type arbitrary abilities.

Never grant `*` automatically.

If enabled without usable abilities, block creation and report it in diagnostics.

### UI

Display:
- Name
- Abilities
- Last used
- Expiration
- Revoke action

Creation:
- Name required
- Abilities from whitelist
- Optional expiration

Plaintext token is shown exactly once after creation.

The token modal must not close by clicking outside and must clearly state that the value cannot be shown again.

### Expiration

Use Sanctum's native per-token expiration.

Optional advanced constraints:

```php
->defaultExpiration(days: 90)
->maxExpiration(days: 365)
```

### User integration

Use Sanctum's official `HasApiTokens` trait.

Do not wrap it behind a package-specific trait without a real need.

Diagnostics must detect missing integration.

## Tenant-scoped API tokens

Default tokens are user-scoped.

Multi-tenant applications may use:

```php
->apiTokens(fn (ApiTokens $tokens) => $tokens
    ->tenantScoped()
    ->abilities([...]))
```

The public API says `tenantScoped()` but storage uses generic `context` terminology.

Supported concepts can therefore include:

```text
Tenant
Team
Workspace
Company
Organization
```

### Token context persistence

Conceptual extra Sanctum columns:

```text
context_type nullable
context_id   nullable
```

Migrations must check for existing columns.

Do not replace Sanctum's token model by default.

### Context resolution

Inside Filament management, the active context can normally come from `Filament::getTenant()`.

For API requests, expose:

```php
interface TokenContextResolver
{
    public function resolve(): ?Model;
}
```

The application binds its own request-context resolver through Laravel's container.

No hard dependency on a tenancy package.

### Context enforcement

Provide middleware/equivalent enforcement ensuring the token's stored context equals the current request context.

A token for Context A must not access Context B.

When `tenantScoped()` is enabled and no active context exists, token creation is unavailable.

Never silently create a global token because context resolution returned null.

## Reauthentication

Sensitive operations use one common service/contract.

Expected consumers:
- Password-sensitive changes
- Disable MFA
- Recovery-code regeneration when appropriate
- Revoke all other sessions
- Sensitive token actions when confirmation is required

Default implementation: secure password confirmation for password-based users.

Applications with external/passwordless auth may bind a custom implementation.

This abstraction must not weaken Filament-native requirements.

## Diagnostics

Provide:

```bash
php artisan filament-complete-user-profile:check
```

Checks only apply to enabled/relevant features.

Examples:

```text
✓ User model
✓ Profile storage
✓ Avatar column
✓ Locale column
✓ MFA contracts
✓ MFA columns
✓ Session driver
✓ Session table
✓ Laravel Sanctum
✓ HasApiTokens integration
✓ Token abilities
✓ Token context resolver
```

Source-code errors, command messages, variable names and function names are English.

## Stable extension points

Expose only meaningful application differences:

- Feature registration
- Feature visibility
- Profile field additions
- Default-field customization
- Profile data mutation hooks
- User model resolution
- Storage mode
- Reauthentication implementation
- SessionStore implementation
- TokenContextResolver
- API-token ability definitions

Views may be publishable for exceptional full overrides, but publishing views is not the normal customization path.

## Proposed structure

```text
src/
├── CompleteUserProfilePlugin.php
├── CompleteUserProfileServiceProvider.php
│
├── Pages/
│   └── CompleteUserProfile.php
│
├── Features/
│   ├── Overview.php
│   ├── Profile.php
│   ├── Security.php
│   ├── Sessions.php
│   └── ApiTokens.php
│
├── Contracts/
│   ├── ProfileFeature.php
│   ├── Reauthentication.php
│   ├── SessionStore.php
│   └── TokenContextResolver.php
│
├── Security/
│   └── PasswordReauthentication.php
│
├── Sessions/
│   ├── DatabaseSessionStore.php
│   └── UserAgent.php
│
├── Tokens/
│   └── ...
│
├── Concerns/
│   └── InteractsWithMultiFactorAuthentication.php
│
└── Commands/
    └── CheckCompleteUserProfile.php
```

Keep classes focused. Do not let the page or plugin become a giant coordinator.

## Explicitly out of scope for v1

- Theme color preference
- Generic delete-account workflow
- Connected social accounts UI
- Google/Socialite provider implementation
- IP geolocation
- Timezone field by default
- Generic JSON `custom_fields`
- Custom TOTP implementation
- Custom token authentication engine
- Shadow Laravel sessions implementation
- Hard dependency on a tenancy package

## Testing strategy

### Plugin / registration
- Profile page registers correctly.
- Normal panel layout is used.
- Defaults match the spec.
- Disabled features are absent.
- Visibility closures work.

### Profile
- Avatar/name/email/locale render by default.
- Existing configured columns are reused.
- Locale resolution order works.
- `fields()` appends defaults.
- `modifyFieldsUsing()` can reorder/remove.
- Extra Eloquent-backed fields persist.
- Native email verification behavior remains intact.

### Migrations
- Existing avatar/locale/MFA columns are not recreated.
- Custom column names are respected.
- Application-owned fields are not destructively dropped.
- Package-owned tables roll back cleanly.

### Password / MFA
- Active Filament guard is used.
- MFA is OFF by default.
- Enabling MFA uses native Filament components.
- Missing model contracts fail diagnostics.
- Recovery codes are ON with MFA.

### Sessions
- OFF by default.
- Only current user's sessions are listed.
- Current session is identified.
- Current session cannot be revoked.
- Supplying another user's session ID cannot revoke it.
- Single/bulk revocation works.
- Unsupported drivers do not crash boot.

### API Tokens
- OFF by default.
- Sanctum is only required when enabled.
- Abilities come only from whitelist.
- No implicit `*`.
- Plaintext is only exposed after creation.
- Revocation works.
- Expiration delegates to Sanctum.
- Missing `HasApiTokens` is detected.

### Tenant-scoped tokens
- Active context required for creation.
- Context persists.
- Context A token cannot access Context B.
- Missing resolver is diagnosed.
- No accidental global fallback.

### Reauthentication
- Default password flow works.
- Sensitive features use the shared abstraction.
- Custom implementations can be container-bound.

## README order

1. Installation
2. Default experience
3. Enable MFA
4. Enable Sessions
5. Enable API Tokens
6. Tenant-scoped tokens
7. Customize Profile fields
8. Structural config
9. Diagnostics
10. Advanced contracts

## Design summary

The package is a Filament account center, not a second authentication framework.

```text
Filament profile page
        │
        ├── Overview
        ├── Profile
        │    ├── Avatar
        │    ├── Name
        │    ├── Email
        │    └── Locale
        │
        ├── Security
        │    ├── Password
        │    └── Filament native MFA
        │
        ├── Laravel database sessions
        │
        └── Laravel Sanctum tokens
             └── optional tenant/context enforcement
```

Defaults stay simple. Advanced applications replace infrastructure through Laravel contracts and service-container bindings without changing the package's public feature API.
