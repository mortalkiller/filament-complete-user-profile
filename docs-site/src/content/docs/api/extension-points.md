---
title: Extension points
description: Replace package infrastructure or embed application-owned account behavior.
---

The package keeps several host-application concerns behind contracts rather than assuming one authentication or tenancy architecture.

## Replace reauthentication

Bind your own implementation of:

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\Reauthentication
```

This is useful for passwordless applications or applications that require a different confirmation step before sensitive account actions.

## Replace session storage

Bind:

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore
```

The built-in implementation targets Laravel database sessions. A custom store can support another session backend while preserving the package's session-management UI contract.

## Tenant token context

Tenant-scoped API tokens require the application to bind:

```php
Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver
```

The resolver owns the application-specific logic for identifying the active API context. `EnsureTokenContext` then compares it with token metadata.

## Profile storage

`ProfileStorage` is resolved by the package according to the configured storage mode. The public contract defines simple `get`, `put` and `putMany` operations for package-managed profile keys.

## Native Filament components

Two public customization APIs intentionally accept native Filament components:

- `Profile::fields()` extends the existing Profile form.
- `AccountSection::schema()` composes a separate account area.

Use a Filament Livewire schema component inside a custom section when the host application needs a stateful domain-specific form.

## Public API stability

Prefer these documented contracts and fluent methods over reaching into package Livewire components, page internals or storage implementation classes. Internal implementation details can change without becoming part of the public extension surface.
