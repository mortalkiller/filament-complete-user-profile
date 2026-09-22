---
title: Profile configuration
description: Configure the built-in profile fields and locale choices.
---

The Profile feature is enabled by default and contains avatar, name, email and locale fields.

The form section is headed "Profile Information", with the description "Update your personal information and profile details." Both strings are translated in English, Portuguese, Spanish and French. Field configuration and the save flow are unchanged.

The section heading and description inherit the active Filament theme's typography; the package does not override their font size, line height or weight.

To set the default account page width, configure `CompleteUserProfilePlugin::make()->maxContentWidth(\Filament\Support\Enums\Width::SixExtraLarge)`. See [Content width](../../api/complete-user-profile-plugin/#content-width) for fallback and routed-page behavior.

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

## Translations

The package provides profile translations for four locales: English (`en`), Portuguese (`pt`), Spanish (`es`) and French (`fr`). The host application can override individual keys without replacing the complete translation set.

To publish the files for inspection or customization:

```bash
php artisan vendor:publish --tag=filament-complete-user-profile-translations
```

With Laravel's default language path, the Portuguese profile file is `lang/vendor/filament-complete-user-profile/pt/profile.php` (and the other locales use `en`, `es` and `fr`). `lang/` is the standard application language directory; if the application uses a custom language path, the provider follows the path resolved by `lang_path()`.

Application-defined text takes precedence; keys that are not defined in the application use the package translation. For example, keep only the key you want to customize:

```php
<?php

return [
    'page' => [
        'heading' => 'A minha área pessoal',
    ],
];
```

The published files initially contain every package key. Keeping a complete copied file means future package translation improvements will not flow through for those keys; prefer keeping only customized keys when possible.

The command preserves existing files by default. Use `--force` only if you intentionally want to overwrite published files, since that also overwrites application customizations.

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
