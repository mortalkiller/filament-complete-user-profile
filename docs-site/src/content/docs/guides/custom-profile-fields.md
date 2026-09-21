---
title: Custom profile fields
description: Extend the existing Profile form with application-owned fields and save hooks.
---

Use custom profile fields when the data belongs naturally inside the existing Profile form.

## Add native Filament fields

```php
use Filament\Forms\Components\TextInput;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->fields([
            TextInput::make('job_title'),
            TextInput::make('phone'),
        ]));
```

The array, or a Closure returning the array, must contain Filament schema `Component` instances. Invalid values cause a `LogicException`.

## Modify the complete field list

```php
->modifyFieldsUsing(function (array $fields): array {
    return $fields;
})
```

The callback receives the assembled list and must return an array containing only Filament schema components.

## Mutate data before save

```php
->mutateDataBeforeSaveUsing(function (array $data): array {
    $data['job_title'] = trim((string) ($data['job_title'] ?? ''));

    return $data;
})
```

The callback must return an array.

## Run logic after save

```php
->afterSave(function ($user, array $data): void {
    // Application-owned side effects...
})
```

## Persistence responsibility

The package does not create arbitrary columns for application fields. Your consuming application owns:

- migrations;
- casts;
- validation specific to its domain;
- relationships;
- any storage outside the package-managed profile fields.

When a feature needs its own navigation destination and stateful behavior, prefer a [custom account section](../custom-account-sections/) rather than forcing it into the Profile form.
