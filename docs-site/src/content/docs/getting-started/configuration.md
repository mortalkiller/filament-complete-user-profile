---
title: Basic configuration
description: Configure account features per Filament panel and structural storage globally.
---

Configuration is deliberately split in two places:

- **Panel behavior** belongs on `CompleteUserProfilePlugin`.
- **Storage structure** belongs in `config/filament-complete-user-profile.php`.

## Panel behavior

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;

CompleteUserProfilePlugin::make()
    ->overview()
    ->profile()
    ->security()
    ->sessions(false)
    ->apiTokens(false);
```

Each panel receives its own plugin instance, so feature availability and navigation can differ by panel.

Feature methods accept either a boolean or a configuration closure. A closure enables the feature before receiving its feature object.

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->avatar(false));
```

## Publish structural config

Publishing the config is optional:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-config
```

The defaults are:

```php
return [
    'user_model' => null,
    'storage' => 'user',
    'columns' => [
        'avatar' => 'avatar_url',
        'locale' => 'locale',
        'mfa' => [
            'secret' => 'app_authentication_secret',
            'recovery_codes' => 'app_authentication_recovery_codes',
            'email_enabled' => 'has_email_authentication',
        ],
    ],
    'profile_table' => 'filament_user_profiles',
];
```

When `user_model` is `null`, the package resolves the model from Laravel's default authentication provider.

See [Storage configuration](../../guides/storage/) for the trade-offs between `user` and `separate`.
