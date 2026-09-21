---
title: Custom account sections
description: Add first-class account areas using native Filament schema components.
---

Use `AccountSection` when an application feature deserves its own destination in the account navigation.

## Register a section

```php
use Filament\Schemas\Components\Text;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;

CompleteUserProfilePlugin::make()
    ->section(
        AccountSection::make('preferences')
            ->label('Preferences')
            ->description('Manage personal preferences.')
            ->sort(25)
            ->schema([
                Text::make('Preferences content'),
            ]),
    );
```

Sections are registered one at a time with `section()`.

## IDs and duplicates

IDs must use lowercase kebab-case, for example `connected-accounts`.

These built-in IDs are reserved:

- `overview`
- `profile`
- `security`
- `sessions`
- `api-tokens`

Registering a reserved ID or the same custom ID twice throws a `LogicException`.

## Visibility and ordering

A custom section defaults to:

- visible: `true`
- sort: `100`

```php
AccountSection::make('preferences')
    ->visible(fn (): bool => auth()->check())
    ->sort(25);
```

Visible custom sections are sorted by their configured sort value and participate in the same navigation as built-in account areas.

## Tabs and sidebar layouts

Custom sections work in both package navigation modes:

- with `Tabs`, they render as native Filament tabs;
- with `Sidebar`, they become native page sub-navigation items and use the same `section` query parameter.

## Persistence

`AccountSection` describes navigation and content composition. It does not automatically persist arbitrary application data to the user model, `ProfileStorage` or a package-owned table.

For stateful application forms, embed a native Filament Livewire schema component and let the application own validation and persistence.
