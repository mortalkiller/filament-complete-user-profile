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
| `page()` | `class-string<Filament\Pages\Page>` | `null` | A concrete custom panel page using `InteractsWithAccountSection`; mutually exclusive with an explicit schema. |

All fluent setters return the same section instance.

## Read-only accessors

```php
getId(): string
getLabel(): string
getDescription(): ?string
getSort(): int
isVisible(): bool
getSchema(): array
getPage(): ?string
```

`getSchema()` normalizes the list with `array_values()` and throws `LogicException` when the callback does not return an array or when an item is not a Filament `Component`.

## Reserved IDs and duplicates

The plugin reserves `overview`, `profile`, `security`, `sessions` and `api-tokens`.

`CompleteUserProfilePlugin::section()` rejects a reserved ID or duplicate custom ID with `LogicException`.

## Integration boundary

`AccountSection` is a navigation and content-composition API. It does not persist arbitrary application data automatically. A section can use application-owned services, external settings packages, Eloquent relationships, Filament actions or embedded Livewire components.

`schema()` accepts Filament schema `Component` instances. Use `page(Billing::class)` for a full routed page, never a Page inside `schema()`. The page must use `Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithAccountSection` and retains its native route, lifecycle and authorization.

Schema and page configuration are mutually exclusive, even with `schema([])`. Resource, auth/profile and cluster pages, pages belonging to a cluster, parameterized page routes and `PageConfiguration` variants are unsupported and throw `LogicException`. One page class can back only one section per panel.

`visible()` controls the menu, not access to a routed page. The page's `canAccess()` controls native access and also filters navigation. Action-level authorization remains the application's responsibility. See [custom account sections](../../guides/custom-account-sections/) for the complete example.
