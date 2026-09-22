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

Use `page()` when the feature needs its own route and page lifecycle while sharing the account navigation:

```php
use Filament\Pages\Page;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithAccountSection;

class Billing extends Page
{
    use InteractsWithAccountSection;

    protected static ?string $slug = 'profile/billing';

    // Define content, actions and authorization as for any custom page.
}

CompleteUserProfilePlugin::make()
    ->section(
        AccountSection::make('billing')
            ->label('Billing')
            ->description('Manage your subscription and payment details.')
            ->sort(60)
            ->page(Billing::class),
    );
```

The plugin registers the page; do not embed it in another page. The trait supplies account presentation without replacing `mount()`, forms, tables, actions or `canAccess()`. It hides the main-sidebar entry by default. Page classes cannot belong to a cluster, use route parameters or `PageConfiguration`, or represent resource, auth/profile or cluster pages. Schema and page configuration cannot be combined. Register each class only once per panel and configure sections before registering the plugin.

A hidden page can still be visited directly if authorized. Use `canAccess()` and action-level checks for security, not `visible()`. The trait rejects requests when no section maps to that page on the current panel. Native tenant pages use the current Filament tenant; custom token tenancy resolvers do not alter page routing. Rebuild route and Filament component caches after changing registrations.

## Visibility, ordering and persistence

A custom section defaults to visible with sort `100`. Visible sections are sorted together with built-in account areas. Inline sections use the `section` query parameter; routed sections use their native page URL. `?section=billing` falls back to an inline area instead of rendering a routed page. If all inline areas are disabled or hidden, the profile redirects to the first visible, accessible page section. If none is available, it retains its empty state.

These IDs are reserved:

- `overview`
- `profile`
- `security`
- `sessions`
- `api-tokens`

Registering a reserved ID or the same custom ID twice throws a `LogicException`.

`AccountSection` does not automatically persist arbitrary application data to the user model, `ProfileStorage` or a package-owned table.
