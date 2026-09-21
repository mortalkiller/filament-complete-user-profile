---
title: Configuration reference
description: Structural package configuration keys and defaults.
---

Publish the optional config with:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-config
```

## Keys

### user_model

```php
'user_model' => null,
```

When `null`, the package resolves the model from Laravel's active default authentication provider. Supply an explicit authenticatable Eloquent model class when necessary.

### storage

```php
'storage' => 'user',
```

Supported package modes are `user` and `separate`.

### columns

```php
'columns' => [
    'avatar' => 'avatar_url',
    'locale' => 'locale',
    'mfa' => [
        'secret' => 'app_authentication_secret',
        'recovery_codes' => 'app_authentication_recovery_codes',
        'email_enabled' => 'has_email_authentication',
    ],
],
```

These names are used by package migrations and profile storage adapters when the corresponding data is stored on the user model.

### profile_table

```php
'profile_table' => 'filament_user_profiles',
```

This table is used when `storage` is `separate`.

## Scope

Feature toggles do not belong in this file. Configure Overview, Profile, Security, Sessions, API Tokens, navigation and custom sections on the `CompleteUserProfilePlugin` instance for each panel.
