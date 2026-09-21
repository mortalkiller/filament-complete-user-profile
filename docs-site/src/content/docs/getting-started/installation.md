---
title: Installation
description: Install Filament Complete User Profile and register the account center.
---

## Requirements

Use the [compatibility guide](../compatibility/) to confirm the supported PHP, Laravel and Filament versions.

## Install the package

```bash
composer require mortalkiller/filament-complete-user-profile
php artisan migrate
```

The service provider is discovered automatically.

## Register the plugin

Register the plugin on each Filament panel that should expose the account center:

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            CompleteUserProfilePlugin::make(),
        ]);
}
```

The package registers its page in Filament's native profile slot, so the normal panel chrome remains available.

## Default account experience

With only `CompleteUserProfilePlugin::make()`, the package enables:

- Overview
- Profile
- Avatar
- Name
- Email
- Locale
- Password management

Authenticator-app MFA, email MFA, browser sessions and API tokens remain disabled until explicitly enabled because they require additional host-application infrastructure.

## Verify the installation

Run the built-in checker:

```bash
php artisan filament-complete-user-profile:check
```

A correctly configured default installation exits with code `0`. See [Diagnostics](../../guides/diagnostics/) for the checks performed.

## Next steps

- Review [basic configuration](../configuration/).
- Choose a [navigation layout](../../guides/navigation/).
- Configure [profile fields](../../guides/profile/).
- Enable optional security capabilities only after satisfying their requirements.
