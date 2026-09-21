---
title: AccountSection
description: API for custom first-class account navigation areas.
---

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\AccountSection
```

## Create a section

```php
AccountSection::make(string $id): static
```

IDs must match lowercase kebab-case. Invalid IDs throw `InvalidArgumentException`.

## Fluent methods

| Method | Argument | Default | Notes |
| --- | --- | --- | --- |
| `label()` | `string|Closure` | headline generated from ID | Empty resolved labels fall back to the generated headline. |
| `description()` | `string|Closure|null` | `null` | Empty resolved descriptions normalize to `null`. |
| `sort()` | `int` | `100` | Controls relative account navigation ordering. |
| `visible()` | `bool|Closure = true` | `true` | Evaluated through Filament's closure evaluation support. |
| `schema()` | `array|Closure` | `[]` | Must resolve to Filament schema components only. |

All fluent setters return the same section instance.

## Read-only accessors

```php
getId(): string
getLabel(): string
getDescription(): ?string
getSort(): int
isVisible(): bool
getSchema(): array
```

`getSchema()` normalizes the list with `array_values()` and throws `LogicException` when the callback does not return an array or when an item is not a Filament `Component`.

## Reserved IDs and duplicates

The plugin reserves `overview`, `profile`, `security`, `sessions` and `api-tokens`.

`CompleteUserProfilePlugin::section()` rejects a reserved ID or duplicate custom ID with `LogicException`.

## Persistence boundary

`AccountSection` is a navigation and content-composition API. It does not persist arbitrary application data automatically.
