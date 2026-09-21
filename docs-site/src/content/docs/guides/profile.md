---
title: Profile configuration
description: Configure the built-in profile fields and locale choices.
---

The Profile feature is enabled by default and contains avatar, name, email and locale fields.

## Configure the feature

```php
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->avatar()
        ->name()
        ->email()
        ->locale());
```

Each built-in field accepts a boolean or a Closure. A boolean enables or disables the field. A Closure keeps the field enabled and receives the native Filament schema component.

```php
use Filament\Schemas\Components\Component;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->avatar(false)
        ->name(fn (Component $field): Component => $field
            ->helperText('Shown in the account area.')));
```

If a modifier does not return a Filament `Component`, the original field is retained.

## Locale choices

Provide locale codes:

```php
->locale(['pt', 'en', 'es', 'fr'])
```

or explicit labels:

```php
->locale([
    'pt' => 'Português',
    'en' => 'English',
])
```

Without explicit options, the package checks `app.available_locales`, then `app.supported_locales`, then falls back to `app.locale`.

## Custom fields

Use `fields()` for fields that belong to the same Profile save flow:

```php
use Filament\Forms\Components\TextInput;

->fields([
    TextInput::make('job_title'),
])
```

Arbitrary application fields are not migrated by this package. Your application owns the corresponding schema and persistence.

For more control, see [Custom profile fields](../custom-profile-fields/).
