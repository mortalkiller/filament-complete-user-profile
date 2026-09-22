---
title: CompleteUserProfilePlugin
description: Panel plugin API for account features, navigation and custom sections.
---

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin
```

Create the plugin with:

```php
CompleteUserProfilePlugin::make()
```

It implements Filament's plugin contract and registers the account page in the panel's profile slot.

## Feature methods

| Method | Argument | Default feature state | Behavior |
| --- | --- | --- | --- |
| `overview()` | `bool|Closure $condition = true` | enabled | Enable, disable or configure Overview. |
| `profile()` | `bool|Closure $condition = true` | enabled | Enable, disable or configure `Profile`. |
| `security()` | `bool|Closure $condition = true` | enabled | Enable, disable or configure `Security`. |
| `sessions()` | `bool|Closure $condition = true` | disabled | Enable, disable or configure `Sessions`. |
| `apiTokens()` | `bool|Closure $condition = true` | disabled | Enable, disable or configure `ApiTokens`. |

When a Closure is supplied, the plugin enables the feature first, passes the feature object to the Closure and replaces the stored feature only when the callback returns a `ProfileFeature`.

```php
CompleteUserProfilePlugin::make()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->abilities([
            'projects:read' => 'Read projects',
        ]));
```

## Page header

```php
pageHeader(PageHeaderPlugin|Closure $plugin): static
```

`CompleteUserProfilePlugin` registers `PageHeaderPlugin` on the panel when the panel does
not already have one. `pageHeader()` accepts a configured `PageHeaderPlugin` instance or a
Closure that receives the plugin and may return a replacement. When the panel already
carries a `PageHeaderPlugin` — registered by the application in either order — that
registration is authoritative and `pageHeader()` is ignored.

```php
hasRegisteredPageHeader(): bool
```

Reports whether the panel's currently registered `PageHeaderPlugin` is the exact instance
this plugin registered during `register()`. It checks the panel's live plugin state rather
than a cached flag, so it returns `false` if the application later registers its own
`PageHeaderPlugin` on the same panel and overwrites this plugin's registration. This backs
the `Page header` row in the `filament-complete-user-profile:check` diagnostics.

## Tenancy resolver

```php
tenancyResolver(TenancyResolver|Closure|class-string<TenancyResolver> $resolver): static
hasCustomTenancyResolver(): bool
getTenancyResolver(): TenancyResolver|Closure|class-string<TenancyResolver>|null
```

The package defaults to its built-in Filament tenancy resolver, which reads `Filament::getTenant()`.

Configure `tenancyResolver()` when the application uses another tenancy implementation. The configured resolver is shared by tenant-scoped token creation, listing, revocation, and API middleware enforcement.

```php
CompleteUserProfilePlugin::make()
    ->tenancyResolver(StanclTenancyResolver::class);
```

The method accepts a resolver class-string, resolver instance, or a closure returning an Eloquent model or `null`.

## Custom sections

```php
section(AccountSection $section): static
getSections(): array
getVisibleSections(): array
```

`section()` rejects reserved built-in IDs and duplicate custom IDs with `LogicException`.

`getVisibleSections()` filters by `AccountSection::isVisible()` and sorts ascending by `getSort()`.

## Feature accessors

```php
getFeature(string $id): ProfileFeature
getFeatures(): array
getVisibleFeatures(): array
```

`getVisibleFeatures()` includes only features that are both enabled and visible, sorted by their configured sort value.

## Resolve the current plugin

```php
CompleteUserProfilePlugin::get()
```

`get()` resolves the plugin from Filament's current or default panel. It throws `LogicException` when no panel is available.

## Per-panel behavior

A plugin instance is registered on a specific Filament panel. Page header configuration, enabled features and custom sections can therefore differ between panels.
