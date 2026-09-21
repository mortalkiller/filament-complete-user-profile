---
title: Navigation
description: How account areas render inside the page header.
---

The account areas render as sub-navigation inside the page header, built with
[`mortalkiller/filament-page-header`](https://github.com/mortalkiller/filament-page-header).
The selected area is reflected in the `section` query parameter, for example
`?section=security`. Invalid or missing section values fall back to the first visible
account area.

You register one plugin. `CompleteUserProfilePlugin` registers `PageHeaderPlugin` on the
panel when it is not already there. To change the header mode without registering a
second plugin:

```php
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;

CompleteUserProfilePlugin::make()
    ->pageHeader(fn (PageHeaderPlugin $header): PageHeaderPlugin => $header->sticky());
```

If your application already registers `PageHeaderPlugin` on the same panel, that
registration is authoritative in either order and `pageHeader()` is ignored.

## Per-panel configuration

Page header configuration belongs to the plugin instance, so different Filament panels
may register different `pageHeader()` closures without changing global package config.
