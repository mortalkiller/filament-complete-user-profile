---
title: Multi-factor authentication
description: Configure authenticator-app and email MFA using Filament's native authentication flow.
---

MFA providers are configured inside the Security feature. Filament remains responsible for the panel MFA challenge flow; this package supplies account-center configuration and storage adapters.

## Authenticator-app MFA

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;

CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->appAuthentication());
```

The authenticatable Eloquent model must implement the package contract and use the package trait:

```php
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

class User extends Authenticatable implements HasMultiFactorAuthentication
{
    use InteractsWithMultiFactorAuthentication;
}
```

The plugin registers Filament's native `AppAuthentication::make()->recoverable()`, so recovery codes are enabled.

## Email MFA

```php
CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->emailAuthentication());
```

The model must implement Filament's email-authentication contract, use the package storage trait and be able to send Laravel notifications:

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

The package sends the verification notification through Laravel's queueable notification mechanism. If the application uses an asynchronous queue connection, a queue worker must be running.

Email MFA adds a server-enforced 60-second resend cooldown. The verification-code expiration itself remains Filament behavior.

## Storage

With `storage => 'user'`, package migrations provide the configured MFA secret, recovery-code and email-enabled columns when they do not already exist.

With `storage => 'separate'`, the package-owned profile table is used instead.

## Authentication flow warning

Do not bypass Filament's normal authentication completion flow from custom login callbacks. Enabling MFA at panel level means entry into that panel must still pass through Filament's MFA challenge.
