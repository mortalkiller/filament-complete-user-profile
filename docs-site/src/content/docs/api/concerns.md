---
title: Public concerns and traits
description: Model traits that adapt package profile storage to Filament MFA contracts.
---

## InteractsWithMultiFactorAuthentication

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication
```

Use this trait on an Eloquent authenticatable model that implements the package `HasMultiFactorAuthentication` contract.

It provides the storage methods used by Filament's authenticator-app MFA provider:

- `getAppAuthenticationSecret()`
- `saveAppAuthenticationSecret()`
- `getAppAuthenticationHolderName()`
- `getAppAuthenticationRecoveryCodes()`
- `saveAppAuthenticationRecoveryCodes()`

With `storage => 'user'`, the trait also applies encrypted casts to the configured secret and recovery-code columns and hides them from model serialization.

The trait throws `LogicException` if used where an authenticatable Eloquent model is required but not available.

## InteractsWithEmailAuthentication

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithEmailAuthentication
```

It implements package-backed storage for Filament's email-MFA state:

- `hasEmailAuthentication(): bool`
- `toggleEmailAuthentication(bool $condition): void`

With user storage, the configured email-enabled column receives a boolean cast.

The consuming model must separately implement Filament's native `HasEmailAuthentication` contract and be able to send notifications.
