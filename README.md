# Filament Complete User Profile

[![Package tests](https://github.com/mortalkiller/filament-complete-user-profile/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/mortalkiller/filament-complete-user-profile/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)

[![Scanned by Plumb](https://plumbphp.dev/badges/mortalkiller/filament-complete-user-profile/scanned.svg)](https://plumbphp.dev/mortalkiller/filament-complete-user-profile)
[![Plumb ecosystem score](https://plumbphp.dev/badges/mortalkiller/filament-complete-user-profile/ecosystem.svg)](https://plumbphp.dev/mortalkiller/filament-complete-user-profile)
[![Plumb maintenance score](https://plumbphp.dev/badges/mortalkiller/filament-complete-user-profile/maintenance.svg)](https://plumbphp.dev/mortalkiller/filament-complete-user-profile)
[![Plumb security score](https://plumbphp.dev/badges/mortalkiller/filament-complete-user-profile/security.svg)](https://plumbphp.dev/mortalkiller/filament-complete-user-profile)
[![Plumb score](https://plumbphp.dev/badges/mortalkiller/filament-complete-user-profile/composite.svg)](https://plumbphp.dev/mortalkiller/filament-complete-user-profile)

A complete, modular account center for Filament 5 and Laravel 13.

It replaces Filament's simple profile screen with a normal panel page and lets you opt into account-security features such as multi-factor authentication, browser session management, Sanctum API tokens, and tenant-scoped tokens while keeping the default setup small.

## Installation

Install the package:

```bash
composer require mortalkiller/filament-complete-user-profile
```

Run migrations:

```bash
php artisan migrate
```

Register the plugin on the Filament panel where you want to expose the account page:

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            CompleteUserProfilePlugin::make(),
        ]);
}
```

The package registers its account page in Filament's native profile slot with the standard panel layout, so the sidebar, topbar, workspace switcher, notifications, and user menu remain available.

You do not need to publish the config for the default setup. The package migrations are defensive: application-owned user columns are created only when missing and are intentionally preserved on rollback.

## Default experience

With only `CompleteUserProfilePlugin::make()`, the package enables:

- Overview
- Profile
- Avatar
- Name
- Email
- Locale
- Password management

Authenticator-app MFA, email MFA, browser sessions, and API tokens are disabled by default because each requires additional application infrastructure.

You can enable or disable the main areas from the panel provider:

```php
CompleteUserProfilePlugin::make()
    ->overview()
    ->profile()
    ->security()
    ->sessions(false)
    ->apiTokens(false);
```

Infrastructure and storage belong in the package config; feature behaviour belongs in the panel provider.

To publish the optional config:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-config
```

## Navigation layout

The account areas use Filament's native schema tabs by default. You can make the choice explicit with `AccountNavigationLayout::Tabs`:

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;

CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Tabs);
```

To use Filament's native left-side page sub-navigation instead, select `AccountNavigationLayout::Sidebar`:

```php
CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Sidebar);
```

`Tabs` renders the visible account areas inside a native Filament `Tabs` schema. `Sidebar` uses Filament's native page sub-navigation at the start of the content area and renders only the selected account area. The selected area is reflected in the `section` query parameter, for example `?section=security`. Invalid or missing section values fall back to the first visible account area.

Both layouts use Filament components and require no package-specific navigation CSS. The layout can be configured independently on each panel because it belongs to the plugin instance registered on that panel.

## Enable MFA

MFA providers are configured inside the Security feature. The package builds on Filament's native MFA providers and keeps Filament responsible for code generation, verification, and the authentication challenge flow.

Enable authenticator-app MFA with recovery codes:

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;

CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->appAuthentication());
```

Enable email-code MFA:

```php
CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->emailAuthentication());
```

Enable both and let Filament manage the available methods:

```php
CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->appAuthentication()
        ->emailAuthentication());
```

For authenticator-app MFA, your authenticatable Eloquent model must implement the package MFA contract and use its storage adapter trait:

```php
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

class User extends Authenticatable implements HasMultiFactorAuthentication
{
    use InteractsWithMultiFactorAuthentication;
}
```

For email MFA, implement Filament's native email-authentication contract, use the package storage trait, and ensure the model can send Laravel notifications:

```php
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Notifications\Notifiable;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithEmailAuthentication;

class User extends Authenticatable implements HasEmailAuthentication
{
    use InteractsWithEmailAuthentication;
    use Notifiable;
}
```

Email MFA adds a server-enforced 60-second cooldown between verification-code sends. During the cooldown, the resend action is disabled and displays the remaining seconds. The verification code keeps Filament's native expiry window, which is currently 4 minutes by default.

The verification email is a queued Laravel notification. If your application uses an asynchronous queue connection such as `database` or `redis`, a queue worker must be running for codes to be delivered:

```bash
php artisan queue:work
```

With `QUEUE_CONNECTION=sync`, the notification is delivered in the request, which can be useful locally, but production applications should keep their normal queue strategy rather than switching to `sync` only for MFA email delivery.

The package-managed migrations provide the default authenticator-app secret, recovery-code, and email-MFA state columns when `storage` is `user`. Existing configured columns are reused rather than replaced.

Because MFA is registered at the Filament panel level, all authentication flows entering that panel must continue through Filament's MFA challenge. Do not bypass the panel's authentication completion flow from a custom social-login callback.

## Enable Sessions

Browser-session management requires Laravel database sessions:

```dotenv
SESSION_DRIVER=database
```

Create Laravel's sessions table if your application does not already have one, then run migrations:

```bash
php artisan make:session-table
php artisan migrate
```

Enable the feature:

```php
CompleteUserProfilePlugin::make()
    ->sessions();
```

The account page lists only sessions belonging to the authenticated user, marks the current session, prevents terminating the current session from the row action, and supports revoking all other sessions after reauthentication.

## Enable API Tokens

API-token management uses Laravel Sanctum. Install Sanctum in the host application and add `HasApiTokens` to the authenticatable model:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
}
```

Then enable the feature with an explicit ability whitelist:

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;

CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->abilities([
            'customers:read' => 'Read customers',
            'customers:write' => 'Manage customers',
        ])
        ->defaultExpiration(30)
        ->maxExpiration(90));
```

The whitelist is mandatory. Wildcard (`*`) abilities and abilities outside the configured list are rejected. The plaintext token is shown only immediately after creation and is cleared from the Livewire component when the confirmation modal is completed.

## Tenant-scoped tokens

Tenant-scoped tokens are opt-in and fail closed. They require all of the following:

1. Sanctum and `HasApiTokens`.
2. A current Filament tenant when a token is created, listed, or revoked.
3. Context columns on `personal_access_tokens`.
4. A `TokenContextResolver` binding for API requests.
5. `EnsureTokenContext` middleware after `auth:sanctum` on tenant-sensitive API routes.

Enable tenant scoping:

```php
CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->tenantScoped()
        ->abilities([
            'customers:read' => 'Read customers',
        ]));
```

Publish and run the opt-in migration:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-token-migrations
php artisan migrate
```

Bind the context resolver in your application service provider. The resolver must return the active tenant/context model for the current API request:

```php
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;

$this->app->bind(TokenContextResolver::class, function (): TokenContextResolver {
    return new class implements TokenContextResolver {
        public function resolve(): ?Model
        {
            $tenant = request()->route('tenant');

            return $tenant instanceof Tenant ? $tenant : null;
        }
    };
});
```

Protect tenant-sensitive API routes after Sanctum authentication:

```php
use Mortalkiller\FilamentCompleteUserProfile\Http\Middleware\EnsureTokenContext;

Route::middleware([
    'auth:sanctum',
    EnsureTokenContext::class,
])->group(function (): void {
    // Tenant-sensitive API routes...
});
```

A missing resolver, missing context, token without context metadata, or context mismatch returns HTTP 403. A token created for one tenant cannot be listed, revoked, or accepted for another tenant through the package's tenant-scoped flow.

## Customize Profile fields

The package keeps Filament's native profile fields and exposes small extension points instead of requiring published views.

Configure locale options with locale codes; common language names are resolved automatically:

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->locale(['pt', 'en', 'es', 'fr']));
```

Associative arrays remain available when you want custom labels:

```php
->locale([
    'pt' => 'Português',
    'en' => 'English',
]);
```

Without explicit locale options, the package reads `app.available_locales`, then `app.supported_locales`, and finally falls back to `app.locale`.

Disable a default field:

```php
CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->avatar(false));
```

Configure an existing field using the native Filament component:

```php
use Filament\Schemas\Components\Component;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->name(fn (Component $field): Component => $field
            ->helperText('Your public display name.')));
```

Add fields:

```php
use Filament\Forms\Components\TextInput;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->fields([
            TextInput::make('job_title'),
        ]));
```

For advanced cases, `Profile` also exposes `modifyFieldsUsing()`, `mutateDataBeforeSaveUsing()`, and `afterSave()`.

Additional custom fields are part of the host application's user/domain model; the package does not automatically create arbitrary columns for them.

## Structural config

Publishing the config is optional:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-config
```

The structural defaults are:

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
            'email_enabled' => 'has_email_authentication',
        ],
    ],

    'profile_table' => 'filament_user_profiles',
];
```

`user_model => null` resolves the model from Laravel's default authentication provider. Set it explicitly when the package should use another authenticatable model.

`storage => 'user'` stores avatar, locale, and package-managed MFA data on the configured user model. The migrations check each configured column before adding it.

`storage => 'separate'` stores package profile data in the package-owned `filament_user_profiles` table. This is useful when the host application should not add package-specific profile columns to its user table.

Feature toggles do not belong in this config. Configure features per Filament panel through `CompleteUserProfilePlugin`.

## Diagnostics

Run the installation checker after configuring the package or enabling an optional capability:

```bash
php artisan filament-complete-user-profile:check
```

The command reports `PASS`, `INFO`, and `FAIL` checks for the registered panels, user model, profile storage, configured profile columns, authenticator-app MFA, email MFA, database sessions, Sanctum, and tenant-token infrastructure.

It exits with code `0` when every enabled capability is ready and code `1` when an enabled capability is misconfigured. Disabled optional capabilities are informational and do not make the command fail.

## Advanced contracts

The package deliberately keeps authentication and tenancy integration replaceable through small contracts.

`Reauthentication` controls how sensitive account actions re-confirm the user's identity. The default implementation uses the current password when available. Applications with passwordless or social-only users can bind their own implementation.

`SessionStore` abstracts browser-session storage. The built-in implementation supports Laravel's database session driver.

`TokenContextResolver` resolves the active tenant/context for an authenticated API request when tenant-scoped tokens are enabled.

`HasMultiFactorAuthentication` is the package-facing contract that connects the user model to Filament's native authenticator-app MFA storage. `InteractsWithMultiFactorAuthentication` is the provided implementation for the package storage modes.

`InteractsWithEmailAuthentication` implements Filament's native email-MFA storage methods using the same package storage modes.

## Roadmap

See [docs/roadmap.md](docs/roadmap.md) for features being considered for future releases.

Roadmap items are exploratory and are not release commitments.

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution and maintenance guidance.

```bash
composer validate --strict
composer check
```

The GitHub Actions matrix validates supported PHP versions. A separate code-quality workflow verifies formatting, static analysis and tracked PHP syntax.

## Security

Please report vulnerabilities privately using the process in [SECURITY.md](SECURITY.md). Use GitHub Issues for ordinary bugs and feature requests.

## License

MIT. See [LICENSE.md](LICENSE.md).
