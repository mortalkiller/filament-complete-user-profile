---
title: Custom profile fields
description: Extend the existing Profile form with application-owned user attributes and save hooks.
---

Use custom profile fields when the data belongs naturally inside the existing Profile form.

## Add the application columns

The package does not create arbitrary domain columns. Add them in your application first:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table): void {
    $table->string('job_title')->nullable();
    $table->string('phone')->nullable();
});
```

Keep your user model's mass-assignment configuration and casts aligned with those fields.

## Add native Filament fields

```php
use Filament\Forms\Components\TextInput;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;

CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->fields([
            TextInput::make('job_title')
                ->maxLength(120),
            TextInput::make('phone')
                ->tel()
                ->maxLength(40),
        ]));
```

The Profile form is bound to the authenticated Eloquent model. Additional fields are filled from model attributes and participate in the normal profile save.

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

## Workbench example

The repository workbench adds `job_title` and `phone` to its demo user table, renders both in Profile, and verifies their values in the browser suite.

When a feature needs its own account navigation destination and behavior, use a [custom account section](../custom-account-sections/) rather than forcing it into the Profile form.
