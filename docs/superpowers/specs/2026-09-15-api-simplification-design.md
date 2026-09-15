# Public API Simplification — Design Specification

## Goal

Simplify the public configuration API of Filament Complete User Profile before the first stable release so there is one obvious, Laravel/Filament-style way to configure each capability.

This refactor changes configuration vocabulary only. It must not change the existing default feature set, page behaviour, storage model, security guarantees, diagnostics semantics, native Filament integrations, or navigation behaviour.

## Design principles

1. A feature is configured at the plugin root; options belonging to that feature are configured inside its feature callback.
2. The same behaviour must not have multiple public configuration methods.
3. Fluent methods should read naturally and align with Filament terminology where practical.
4. Defaults remain useful: `CompleteUserProfilePlugin::make()` continues to provide Overview, Profile, Avatar, Name, Email, Locale, Security and Password without extra configuration.
5. Optional capabilities remain opt-in.
6. Since the package is still on the initial implementation branch and has not reached a stable public release, obsolete API methods are removed instead of retained as aliases or deprecated compatibility shims.

## Canonical API

The intended advanced configuration is:

```php
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;

CompleteUserProfilePlugin::make()
    ->navigation(AccountNavigationLayout::Sidebar)
    ->profile(fn (Profile $profile): Profile => $profile
        ->locale(['pt', 'en', 'es', 'fr']))
    ->security(fn (Security $security): Security => $security
        ->password()
        ->appAuthentication()
        ->emailAuthentication())
    ->sessions()
    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
        ->abilities([
            'customers:read' => 'Read customers',
            'customers:write' => 'Manage customers',
        ])
        ->defaultExpiration(30)
        ->maxExpiration(90)
        ->tenantScoped());
```

The minimal setup remains:

```php
CompleteUserProfilePlugin::make();
```

Explicit enable/disable remains available on the feature entry points:

```php
CompleteUserProfilePlugin::make()
    ->overview(false)
    ->profile(false)
    ->security(false)
    ->sessions(false)
    ->apiTokens(false);
```

A feature callback enables that feature and configures it:

```php
CompleteUserProfilePlugin::make()
    ->sessions(fn (Sessions $sessions): Sessions => $sessions
        ->visible(fn (): bool => Filament::getTenant() !== null));
```

## Exact public API changes

### Plugin root

Keep:

```text
overview(bool|Closure $condition = true)
profile(bool|Closure $condition = true)
security(bool|Closure $condition = true)
sessions(bool|Closure $condition = true)
apiTokens(bool|Closure $condition = true)
```

Rename:

```text
navigationLayout(AccountNavigationLayout $layout)
→ navigation(AccountNavigationLayout $layout)
```

Remove the redundant configuration aliases:

```text
overviewWith()
profileWith()
securityWith()
sessionsWith()
apiTokensWith()
```

Remove security internals from the plugin root:

```text
multiFactorAuthentication()
emailAuthentication()
```

Security providers must only be configured through `security(...)`.

The read accessor `getNavigationLayout()` remains because it describes the stored state clearly and is used internally by the page. `AccountNavigationLayout` remains the enum name. No additional aliases such as `sidebarNavigation()` or `tabsNavigation()` are introduced.

### Security feature

Rename the authenticator-app capability:

```text
multiFactorAuthentication()
→ appAuthentication()
```

For internal consistency, rename the corresponding feature state/query methods:

```text
hasMultiFactorAuthentication()
→ hasAppAuthentication()

getMultiFactorAuthenticationRequirementIssue()
→ getAppAuthenticationRequirementIssue()
```

Keep:

```text
password()
emailAuthentication()
hasPassword()
hasEmailAuthentication()
getEmailAuthenticationRequirementIssue()
```

The renamed `appAuthentication()` still registers Filament's native recoverable `AppAuthentication` provider. `emailAuthentication()` still registers Filament's native `EmailAuthentication` provider. Both may be enabled simultaneously.

This refactor does not rename the existing package storage contract `HasMultiFactorAuthentication` or the `InteractsWithMultiFactorAuthentication` trait. They are infrastructure integration types rather than configuration DSL, and changing them is outside this API-simplification scope.

## APIs intentionally unchanged

### Profile

Keep the current fluent API:

```text
avatar()
name()
email()
locale()
fields()
modifyFieldsUsing()
mutateDataBeforeSaveUsing()
afterSave()
```

`locale(['pt', 'en'])` continues to resolve human-readable language names through `LocaleRegistry`, while associative arrays continue to allow manual label overrides.

### API Tokens

Keep:

```text
abilities()
defaultExpiration()
maxExpiration()
tenantScoped()
```

Do not combine the expiration methods into a compound method. The explicit methods are clearer and easier to discover through autocomplete.

### Shared feature behaviour

Keep:

```text
enabled()
visible()
sort()
```

These remain useful advanced feature-level controls.

## Behavioural invariants

The refactor must preserve all existing behaviour:

- Tabs remain the default navigation layout.
- Sidebar continues to use native Filament sub-navigation and `?section=` Livewire URL state.
- Overview, Profile and Security remain enabled by default.
- Password remains enabled by default.
- Authenticator-app MFA, email MFA, Sessions and API Tokens remain disabled by default.
- Authenticator-app MFA remains recoverable and uses Filament's native provider.
- Email MFA remains Filament-native.
- TOTP and email MFA may be enabled together.
- Session and token security behaviour is unchanged.
- Locale mapping and shipped translations are unchanged.
- `storage=user` and `storage=separate` behaviour is unchanged.
- Diagnostics continue to validate exactly the enabled capabilities.

## Documentation changes

Update every package-owned example to use the canonical API, including:

- `README.md`
- the original package design specification
- `docs/roadmap.md`
- any verification/development documentation containing old method names

The Passkeys roadmap example must use `->appAuthentication()` rather than `->multiFactorAuthentication()`.

Documentation must not show removed aliases or root-level MFA methods.

## Test strategy

Use TDD for the refactor.

Add or update tests that prove:

1. `navigation()` defaults to Tabs and can select Sidebar.
2. `navigationLayout()` no longer exists.
3. All `*With()` methods no longer exist.
4. Root-level `multiFactorAuthentication()` and `emailAuthentication()` no longer exist.
5. `security(fn (Security $security) => $security->appAuthentication())` registers Filament `AppAuthentication` with recovery enabled.
6. `security(...->emailAuthentication())` registers Filament `EmailAuthentication`.
7. `appAuthentication()` and `emailAuthentication()` work together.
8. Feature callbacks still enable and configure Profile, Security, Sessions and API Tokens.
9. Boolean feature toggles still work.
10. Sidebar Livewire regression tests remain green after the setter rename.
11. Diagnostics use the renamed Security query/requirement methods and retain their existing behaviour.
12. README/roadmap examples no longer reference the removed API names.

After implementation, run the full package verification matrix on PHP 8.3, 8.4 and 8.5 plus the dedicated Code quality workflow.

## Out of scope

This refactor does not:

- add Passkeys/WebAuthn;
- add Fortify;
- change MFA storage contracts or traits;
- change database schemas;
- change translation keys solely for naming aesthetics;
- change the Profile, Sessions or API Tokens feature behaviour;
- change the navigation implementation;
- add backwards-compatibility aliases for the removed pre-release API.

## Result

The package should expose one predictable hierarchy:

```text
CompleteUserProfilePlugin
├── navigation()
├── overview()
├── profile()
│   ├── avatar()
│   ├── name()
│   ├── email()
│   └── locale()
├── security()
│   ├── password()
│   ├── appAuthentication()
│   └── emailAuthentication()
├── sessions()
└── apiTokens()
    ├── abilities()
    ├── defaultExpiration()
    ├── maxExpiration()
    └── tenantScoped()
```

The API should grow by adding configuration to the feature that owns it, not by adding shortcuts to the plugin root.
