---
title: Installation
description: Install Filament Complete User Profile and register the account center.
---

## Requirements

Use the [compatibility guide](../compatibility/) to confirm the supported PHP, Laravel and Filament versions.

## Install the package

```bash
composer require mortalkiller/filament-complete-user-profile
```

The service provider is discovered automatically.

Publish the registered styles after installation and updates, unless your deployment already runs `filament:upgrade`:

```bash
php artisan filament:assets
```

The security-card CSS is loaded through Filament's asset registry; it does not require a custom theme rebuild.

## Optional: publish translations

The package ships translations for English, Portuguese, Spanish and French. Publish them when you want to inspect or customize the package text in the host application:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-translations
```

With the default language path, the files are copied to `lang/vendor/filament-complete-user-profile` (for example, `lang/vendor/filament-complete-user-profile/pt/profile.php`). If the application uses a custom language path, the provider follows the path resolved by `lang_path()`. Text customized by the application takes precedence over the package defaults; when a key is not defined by the application, the package translation is used.

The published files contain all package keys. Keep only the keys you customize where possible, so future translation improvements from the package can still apply to the remaining keys.

Publishing without `--force` keeps existing application translation files. Add `--force` only when you deliberately want to replace those files; it overwrites customizations.

## Choose storage before the first migration

The default mode stores package-managed profile data on the authenticatable model:

```php
'storage' => 'user',
```

For the default mode, run:

```bash
php artisan migrate
```

If you want the package-owned profile table instead, publish the config first:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-config
```

Set:

```php
'storage' => 'separate',
```

and only then run:

```bash
php artisan migrate
```

Laravel records package migrations as executed even when a storage-specific migration has nothing to do for the selected mode. Treat storage mode as an installation-time structural decision. Changing modes later requires an application-owned migration/data migration.

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
- Review the [account navigation](../../guides/navigation/).
- Configure [profile fields](../../guides/profile/).
- Customize [profile translations](../../guides/profile/#translations).
- Enable optional security capabilities only after satisfying their requirements.
