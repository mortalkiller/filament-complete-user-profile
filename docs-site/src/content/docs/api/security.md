---
title: Security feature
description: Reference for password and multi-factor authentication configuration.
---

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Features\Security
```

`Security` extends `AbstractFeature` and has default sort `30`.

## Configuration methods

```php
password(bool|Closure $value = true): static
appAuthentication(bool|Closure $value = true): static
emailAuthentication(bool|Closure $value = true): static
```

Defaults:

| Capability | Default |
| --- | --- |
| Password management | enabled |
| Authenticator-app MFA | disabled |
| Email MFA | disabled |

The Closures are evaluated through Filament's closure evaluation support when the corresponding `has...` method is called.

## Accessors

```php
hasPassword(): bool
hasAppAuthentication(): bool
hasEmailAuthentication(): bool
```

## Requirement checks

```php
getAppAuthenticationRequirementIssue(Authenticatable $user): ?string
getEmailAuthenticationRequirementIssue(Authenticatable $user): ?string
```

Authenticator-app MFA requires the user to implement:

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication
```

Email MFA requires Filament's native email-authentication contract. The authenticated user must also be an Eloquent model with a callable `notify()` method so Laravel notifications can be delivered.

These methods return `null` when the relevant requirement is satisfied or the feature itself is not enabled.
