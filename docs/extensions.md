# Extending the account center

The package owns the account shell and its built-in features. Application-specific data stays in the consuming application.

The local workbench demonstrates three extension patterns:

1. custom fields stored on the authenticated `User`;
2. a custom section persisted by `spatie/laravel-settings`;
3. a custom section backed by an Eloquent relationship.

## Custom fields on the user model

Add the domain columns in your application:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('users', function (Blueprint $table): void {
    $table->string('job_title')->nullable();
    $table->string('phone')->nullable();
});
```

Then register ordinary Filament form components:

```php
use Filament\Forms\Components\TextInput;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
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

The profile form is bound to the authenticated Eloquent model. Additional fields are therefore filled from model attributes and saved through the normal Filament profile update. Keep the model's mass-assignment configuration, casts and domain validation aligned with the fields you add.

## Settings-backed section

`AccountSection` does not care where its state is stored. This makes it suitable for an application service or an external settings package.

The workbench installs `spatie/laravel-settings:^3.9` as a development-only dependency and registers this example settings class:

```php
namespace Workbench\App\Settings;

use Spatie\LaravelSettings\Settings;

final class DemoSettings extends Settings
{
    public string $timezone;

    public bool $weekly_digest;

    public static function group(): string
    {
        return 'demo';
    }
}
```

The section renders current values and persists updates with a normal Filament action:

```php
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;

AccountSection::make('application-settings')
    ->label('Application Settings')
    ->schema([
        TextEntry::make('timezone')
            ->state(fn (): string => app(DemoSettings::class)->timezone),

        Actions::make([
            Action::make('editSettings')
                ->schema([
                    Select::make('timezone')
                        ->options([
                            'Europe/Lisbon' => 'Europe/Lisbon',
                            'Europe/London' => 'Europe/London',
                        ])
                        ->required(),
                    Toggle::make('weekly_digest'),
                ])
                ->fillForm(fn (): array => app(DemoSettings::class)->toArray())
                ->action(function (array $data): void {
                    $settings = app(DemoSettings::class);
                    $settings->timezone = (string) $data['timezone'];
                    $settings->weekly_digest = (bool) ($data['weekly_digest'] ?? false);
                    $settings->save();
                }),
        ]),
    ]);
```

The Spatie config, settings table and settings migrations belong to the host application. This package only provides the account section composition point.

## Relationship-backed section

The workbench user exposes an ordinary Eloquent relationship:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function addresses(): HasMany
{
    return $this->hasMany(Address::class);
}
```

A section can render that relation with native Filament entries:

```php
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;

AccountSection::make('addresses')
    ->schema([
        RepeatableEntry::make('addresses')
            ->state(fn (): array => auth()->user()
                ->addresses()
                ->get(['label', 'line_one', 'city', 'country_code'])
                ->toArray())
            ->schema([
                TextEntry::make('label'),
                TextEntry::make('line_one')->label('Address'),
                TextEntry::make('city'),
                TextEntry::make('country_code')->label('Country'),
            ]),
    ]);
```

The workbench also includes an action that creates a related address. For richer CRUD, filters or pagination, render an application-owned Livewire component containing a Filament Table:

```php
use Filament\Schemas\Components\Livewire;

AccountSection::make('addresses')
    ->schema([
        Livewire::make(\App\Livewire\Account\AddressesTable::class),
    ]);
```

## When to use a separate Filament Page

`AccountSection::schema()` accepts Filament schema components. A full `Filament\Pages\Page` is a routed page with its own lifecycle and is not a schema component.

Use `AccountSection::page(Billing::class)` with the `InteractsWithAccountSection` trait when a page needs its own route and lifecycle while sharing account navigation. The plugin registers it with Filament. Do not mount a complete Filament Page inside another page. `schema()` and `page()` are mutually exclusive; routed pages keep native `canAccess()` and application-owned action authorization. Visibility only controls the menu. See the README's routed-page example for unsupported page types and cache deployment guidance.

[Back to the README](../README.md)
