---
title: Public contracts
description: Replaceable package interfaces for features and host-application integrations.
---

## ProfileFeature

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature
```

```php
getId(): string
enabled(bool|Closure $condition = true): static
isEnabled(): bool
isVisible(): bool
getSort(): int
```

Built-in features implement this interface through `AbstractFeature`.

## ProfileStorage

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage
```

```php
get(Authenticatable $user, string $key): mixed
put(Authenticatable $user, string $key, mixed $value): void
putMany(Authenticatable $user, array $values): void
```

The service provider binds this contract to user-model or separate-table storage according to package config.

## Reauthentication

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\Reauthentication
```

```php
isAvailable(Authenticatable $user): bool
getFormSchema(): array
confirm(Authenticatable $user, array $data): void
```

`getFormSchema()` returns Filament schema components. Applications can replace the default password-based strategy.

## SessionStore

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore
```

```php
sessionsFor(Authenticatable $user): Collection
revoke(Authenticatable $user, string $sessionId): void
revokeOthers(Authenticatable $user, string $currentSessionId): void
isSupported(): bool
getUnsupportedReason(): ?string
```

The package provides a database-session implementation.

## TenancyResolver

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver
```

```php
resolve(): ?Model
```

The resolver supplies the active tenant/context model for all tenant-aware package operations. The package provides a Filament-native default resolver. Applications using another tenancy implementation can replace it through `CompleteUserProfilePlugin::tenancyResolver()`.

## TokenContextResolver

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver
```

This legacy contract now extends `TenancyResolver` and remains available for backwards compatibility. New integrations should implement `TenancyResolver`.

## HasMultiFactorAuthentication

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication
```

This package-facing contract connects the authenticatable model to Filament's authenticator-app MFA storage expectations. The package trait provides the intended implementation.
