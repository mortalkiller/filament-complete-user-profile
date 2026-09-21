---
title: Navigation layouts
description: Choose native Filament tabs or sidebar sub-navigation.
---

The default navigation layout is `AccountNavigationLayout::Tabs`.

## Tabs

```php
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;

CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Tabs);
```

Visible account features and custom sections are rendered as native Filament schema tabs.

## Sidebar

```php
CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Sidebar);
```

Sidebar mode uses Filament's native page sub-navigation and renders only the selected account area.

The selected area is represented by the `section` query parameter, for example:

```text
?section=security
```

A missing, invalid or hidden section falls back to the first visible area.

## Per-panel configuration

Navigation belongs to the plugin instance, so different Filament panels may use different layouts without changing global package config.
