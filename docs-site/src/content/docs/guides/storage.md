---
title: Storage configuration
description: Store package-managed profile and MFA data on the user model or in a separate table.
---

The package supports two storage modes.

## User storage

This is the default:

```php
'storage' => 'user',
```

Package-managed profile fields are stored on the configured authenticatable model.

Default configured columns are:

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

Change a column name when the application already has an equivalent field and should reuse it.

The migrations check whether configured columns already exist before adding them. Application-owned columns are intentionally preserved by rollback rather than being dropped blindly.

## Separate storage

```php
'storage' => 'separate',
'profile_table' => 'filament_user_profiles',
```

The package uses its own polymorphic profile record instead of adding package-specific profile and MFA values to the user table.

## User model resolution

```php
'user_model' => null,
```

With `null`, the package resolves the authenticatable model from Laravel's default authentication provider. Set a class explicitly when the Filament application uses a different model.

## Application-owned custom fields

Neither storage mode turns arbitrary fields passed to `Profile::fields()` or `AccountSection::schema()` into package-managed data.

Your application remains responsible for domain-specific schema and persistence.
