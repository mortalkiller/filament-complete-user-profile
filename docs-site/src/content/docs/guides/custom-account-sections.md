---
title: Custom account sections
description: Add first-class account areas backed by application services, settings packages or Eloquent relationships.
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
            ->sort(60)
            ->schema([
                Text::make('Preferences content'),
            ]),
    );
```

Sections are registered one at a time with `section()`. IDs must use lowercase kebab-case.

## External persistence: Spatie Laravel Settings

A section owns composition, not persistence. It can therefore use a service or package already owned by your application.

The local workbench demonstrates `spatie/laravel-settings`:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;

AccountSection::make('application-settings')
    ->schema([
        TextEntry::make('weekly_digest')
            ->state(fn (): string => app(DemoSettings::class)->weekly_digest ? 'Enabled' : 'Disabled'),

        Actions::make([
            Action::make('editSettings')
                ->schema([
                    Toggle::make('weekly_digest'),
                ])
                ->fillForm(fn (): array => app(DemoSettings::class)->toArray())
                ->action(function (array $data): void {
                    $settings = app(DemoSettings::class);
                    $settings->weekly_digest = (bool) $data['weekly_digest'];
                    $settings->save();
                }),
        ]),
    ]);
```

The settings class, settings table, settings migrations and dependency remain application-owned. `spatie/laravel-settings` is not a runtime dependency of this package.

## Eloquent relationships

A section can render application relationships directly:

```php
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

AccountSection::make('addresses')
    ->schema([
        RepeatableEntry::make('addresses')
            ->state(fn (): array => auth()->user()
                ->addresses()
                ->get(['label', 'line_one', 'city'])
                ->toArray())
            ->schema([
                TextEntry::make('label'),
                TextEntry::make('line_one')->label('Address'),
                TextEntry::make('city'),
            ]),
    ]);
```

The workbench includes an `Addresses` section backed by `DemoUser::addresses()` and an action that creates a related address.

## Rich stateful interfaces

For a full table, filters, pagination or a larger form, compose the section from an application-owned Livewire component:

```php
use Filament\Schemas\Components\Livewire;

AccountSection::make('addresses')
    ->schema([
        Livewire::make(\App\Livewire\Account\AddressesTable::class),
    ]);
```

The Livewire component can use native Filament Tables, Forms, Actions and Schemas.

## Full Filament Pages

`AccountSection::schema()` accepts Filament schema `Component` instances. A `Filament\Pages\Page` is a routed page and is not accepted directly as section content.

Use schema components or a Livewire component when the feature should remain inside the account center. Register a separate Filament Page when the feature needs its own route and page lifecycle. The current `AccountSection` API does not provide a custom navigation URL for redirecting a section to another page.

## Visibility, ordering and persistence

A custom section defaults to visible with sort `100`. Visible custom sections are sorted together with built-in account areas and selected through the `section` query parameter.

These IDs are reserved:

- `overview`
- `profile`
- `security`
- `sessions`
- `api-tokens`

Registering a reserved ID or the same custom ID twice throws a `LogicException`.

`AccountSection` does not automatically persist arbitrary application data to the user model, `ProfileStorage` or a package-owned table.
