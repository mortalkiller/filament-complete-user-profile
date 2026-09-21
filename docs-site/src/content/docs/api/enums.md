---
title: Enums
description: Public enum values used by package configuration.
---

## AccountNavigationLayout

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout
```

This is a string-backed enum:

| Case | Value | Behavior |
| --- | --- | --- |
| `AccountNavigationLayout::Tabs` | `tabs` | Render visible account areas as native Filament schema tabs. |
| `AccountNavigationLayout::Sidebar` | `sidebar` | Render native page sub-navigation and only the selected area. |

Use it with:

```php
CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Sidebar);
```

The plugin defaults to `Tabs`.
