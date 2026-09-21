# Custom Account Sections — Design Specification

## Goal

Allow host applications to add their own navigable account areas to Filament Complete User Profile without extending package internals, publishing views, or introducing package-managed persistence for application-specific data.

The API must feel native to Laravel and Filament: fluent, explicit, discoverable through autocomplete, strongly typed where practical, and composed from Filament schema components.

## Design principles

1. Custom account sections are navigation/content units, not persistence abstractions.
2. Host applications own domain data, migrations, validation and save behaviour for custom content.
3. The package should expose one obvious fluent API instead of arrays of configuration or required inheritance.
4. Native Filament schema components are the extension surface. The package does not create a parallel rendering system.
5. Built-in features remain unchanged in responsibility and behaviour.
6. Custom sections work with both `AccountNavigationLayout::Tabs` and `AccountNavigationLayout::Sidebar`.
7. Invalid or ambiguous configuration must fail early with clear exceptions rather than silently overriding built-ins.
8. Existing installations that do not register custom sections behave exactly as before.

## Public API

The canonical API is:

```php
use Filament\Schemas\Components\Livewire;
use Filament\Support\Icons\Heroicon;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;

CompleteUserProfilePlugin::make()
    ->section(
        AccountSection::make('preferences')
            ->label('Preferences')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->description('Manage your personal preferences.')
            ->sort(25)
            ->visible(fn (): bool => true)
            ->schema([
                Livewire::make(\App\Livewire\Account\Preferences::class),
            ]),
    );
```

Multiple custom sections are registered by calling `section()` repeatedly:

```php
CompleteUserProfilePlugin::make()
    ->section(AccountSection::make('preferences')->schema([...]))
    ->section(AccountSection::make('notifications')->schema([...]))
    ->section(AccountSection::make('connected-accounts')->schema([...])));
```

There is intentionally no `sections([...])` bulk configurator in the first version. A single canonical registration method keeps configuration easy to discover and read.

## `AccountSection`

Create `Mortalkiller\FilamentCompleteUserProfile\AccountSection` as a small fluent value/configuration object.

### Construction

```php
AccountSection::make(string $id): static
```

The identifier is immutable after construction and is used in the `section` query parameter.

Valid identifiers use lowercase kebab-case:

```text
preferences
connected-accounts
notification-settings
```

Reject empty or malformed identifiers early with `InvalidArgumentException`.

The package reserves the built-in feature IDs:

```text
overview
profile
security
sessions
api-tokens
```

Registering a custom section with a reserved ID must throw `LogicException`. Registering the same custom section ID twice must also throw `LogicException`.

### Fluent methods

The first version exposes only:

```text
make(string $id)
label(string|Closure $label)
icon(string|BackedEnum|null $icon)
description(string|Closure|null $description)
sort(int $sort)
visible(bool|Closure $condition = true)
schema(array|Closure $components)
```

All setters return `static`.

Do not add `badge()`, `group()`, persistence callbacks, save hooks, view paths, Livewire shortcuts, or enable/disable aliases in this version.

### Defaults

- `label`: generated from the ID using `Str::headline()`, e.g. `connected-accounts` → `Connected Accounts`.
- `icon`: `null`.
- `description`: `null`.
- `sort`: `100`, placing unconfigured custom sections after the built-in default sections.
- `visible`: `true`.
- `schema`: empty array.

### Schema

`schema()` accepts either an array of Filament `Filament\Schemas\Components\Component` instances or a closure returning such an array.

A closure returning a non-array value must throw `LogicException`. Any schema entry that is not a Filament schema component must throw `LogicException`.

The package does not persist values from a custom section automatically. For persistent custom UI, applications should provide a Livewire component, Filament form component, action, or other schema component that owns its own data flow.

## Plugin storage and registration

`CompleteUserProfilePlugin` keeps built-in features and custom sections as separate collections:

```text
features  → package-owned capabilities
sections  → application-owned navigation/content areas
```

Add:

```php
public function section(AccountSection $section): static
public function getSections(): array
public function getVisibleSections(): array
```

`section()` validates reserved and duplicate IDs before storing the object.

`getVisibleSections()` filters by `AccountSection::isVisible()` and preserves deterministic sort order.

Do not convert built-in feature classes to `AccountSection`. Security, MFA, Sessions and API Tokens keep their current feature classes and runtime responsibilities.

## Page navigation model

`CompleteUserProfile` should build one ordered list at render time from:

1. visible built-in `ProfileFeature` instances;
2. visible custom `AccountSection` instances.

Sort the merged list by each item's `sort` value ascending. If two items share a sort value, preserve registration order so behaviour is deterministic.

A custom section with `sort(25)` therefore appears between Profile (`20`) and Security (`30`).

The page may use internal helper methods and union types/docblocks for `ProfileFeature|AccountSection`; no new public extension contract is required solely for navigation.

## Labels, icons and descriptions

Built-in feature labels continue to use package translations.

Custom sections use their `AccountSection` configuration:

- `getLabel()` for sidebar and tab labels;
- `getIcon()` for custom sidebar items and tabs when configured;
- `getDescription()` for the rendered Filament `Section`.

Do not change built-in feature icons or labels as part of this work.

## Sidebar behaviour

With `AccountNavigationLayout::Sidebar`, custom sections appear in Filament native page sub-navigation.

Example URL:

```text
/profile?section=preferences
```

The current `section` Livewire state remains the source of truth.

A custom section is active when its ID matches the selected section. Invisible sections are not returned by navigation and cannot become active through direct query-string selection.

If the query string points to a missing or invisible section, fall back to the first visible account item, matching current behaviour for invalid built-in section IDs.

## Tabs behaviour

With `AccountNavigationLayout::Tabs`, custom sections appear as native Filament schema tabs alongside built-in features.

The tab uses the custom label and optional icon, and its schema contains the rendered custom section content.

This work does not redesign how the existing Tabs layout stores active-tab state. It only ensures custom sections participate in the existing native Tabs rendering model.

## Rendering custom content

When a custom section is rendered, wrap its configured schema in a native Filament `Section`:

```php
Section::make($section->getLabel())
    ->description($section->getDescription())
    ->schema($section->getSchema());
```

If no description or icon is configured, render normally without requiring placeholder values.

Views remain package-owned and unchanged unless strictly required by Filament rendering. No custom section view publishing mechanism is introduced.

## Persistence boundary

Custom account sections do not automatically read from or write to the configured User model or `ProfileStorage`.

For user-table fields that naturally belong inside the existing Profile form, continue using:

```php
->profile(fn (Profile $profile): Profile => $profile
    ->fields([
        TextInput::make('job_title'),
    ]));
```

For a separate navigable area with application-owned persistence, use a custom section containing a Livewire or other Filament schema component:

```php
->section(
    AccountSection::make('preferences')
        ->schema([
            Livewire::make(PreferencesForm::class),
        ]),
)
```

This separation must be explicit in the README.

## Error behaviour

Fail early with English exception messages for:

- malformed custom section IDs;
- a custom section ID colliding with a built-in feature;
- duplicate custom section registration;
- schema callbacks returning non-arrays;
- schema arrays containing non-Filament components.

Do not silently rename, overwrite or ignore invalid sections.

## Documentation

Add a `Custom account sections` section to `README.md` after the existing custom-profile-fields documentation.

The documentation must cover:

1. a minimal custom section;
2. label, icon, description, sort and visibility;
3. using a Livewire component inside `schema()`;
4. the distinction between `Profile::fields()` and `AccountSection`;
5. the persistence boundary;
6. reserved built-in IDs;
7. compatibility with Tabs and Sidebar navigation.

Use the canonical fluent API only.

## Test strategy

Use TDD. Tests must fail before production implementation is added.

Cover:

1. `AccountSection::make()` and fluent return values.
2. generated default label and defaults.
3. explicit label, icon, description and sort.
4. array and closure schemas.
5. malformed IDs.
6. invalid schema callback return values and invalid schema entries.
7. plugin `section()` registration.
8. duplicate and reserved-ID rejection.
9. conditional visibility.
10. combined built-in/custom sorting.
11. Sidebar labels, active state, URLs and optional icon.
12. invalid/invisible section fallback.
13. Tabs rendering accepts custom sections and icons.
14. existing built-in navigation and profile behaviour remains unchanged.
15. README documents the canonical API and persistence boundary.

After focused tests are green, run Composer validation, Pint, PHPStan, the full PHPUnit suite, the distribution-archive verification, and the GitHub Actions PHP 8.3/8.4/8.5 matrix.

## Out of scope

This feature does not:

- automatically persist custom-section fields;
- create application-specific migrations;
- add new ProfileStorage keys;
- add badges or navigation groups;
- add custom views or published templates;
- add a bulk `sections()` configurator;
- replace built-in features with custom sections;
- redesign Tabs state handling;
- change MFA, Sessions or API Token behaviour.

## Result

The package gains one predictable extension point:

```text
CompleteUserProfilePlugin
├── navigation()
├── overview()
├── profile()
├── security()
├── sessions()
├── apiTokens()
└── section(
    AccountSection::make(...)
        ├── label()
        ├── icon()
        ├── description()
        ├── sort()
        ├── visible()
        └── schema()
    )
```

Developers can add first-class account navigation areas using familiar Filament schema composition while the package remains neutral about application-domain persistence.