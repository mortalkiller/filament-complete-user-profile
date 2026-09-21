---
title: Profile feature
description: Reference for built-in profile fields, custom fields and save hooks.
---

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Features\Profile
```

`Profile` extends the package `AbstractFeature`. Its default sort value is `20`.

## Built-in field methods

```php
avatar(bool|Closure $value = true): static
name(bool|Closure $value = true): static
email(bool|Closure $value = true): static
locale(bool|array|Closure $value = true): static
```

Avatar, name, email and locale are enabled by default.

For avatar, name and email:

- a boolean enables or disables the field;
- a Closure enables the field and receives the native Filament component as its modifier.

For `locale()`, an array enables the field and sets explicit locale options.

## Custom fields

```php
fields(array|Closure $fields): static
getAdditionalFields(): array
```

The configured array or Closure result must contain only Filament schema `Component` objects. Invalid results throw `LogicException`.

## Field-list hook

```php
modifyFieldsUsing(?Closure $callback): static
modifyFields(array $fields): array
```

The callback receives the full field array and must return an array containing only Filament components.

## Save hooks

```php
mutateDataBeforeSaveUsing(?Closure $callback): static
mutateDataBeforeSave(array $data): array

afterSave(?Closure $callback): static
runAfterSave(Authenticatable $user, array $data): void
```

The data mutator must return an array or a `LogicException` is thrown.

## State accessors

```php
hasAvatar(): bool
hasName(): bool
hasEmail(): bool
hasLocale(): bool
getLocaleOptions(): array
```

Locale option resolution prefers `app.available_locales`, then `app.supported_locales`, then `app.locale` when no explicit options were configured.

## Native component modifiers

The package exposes:

```php
configureAvatar(Component $field): Component
configureName(Component $field): Component
configureEmail(Component $field): Component
configureLocale(Component $field): Component
```

When a field modifier does not return a `Component`, the original component is retained.
