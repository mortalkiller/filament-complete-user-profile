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

## Navigation

```php
navigation(AccountNavigationLayout $layout): static
getNavigationLayout(): AccountNavigationLayout
```

The default layout is `AccountNavigationLayout::Tabs`.

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

A plugin instance is registered on a specific Filament panel. Navigation layout, enabled features and custom sections can therefore differ between panels.
