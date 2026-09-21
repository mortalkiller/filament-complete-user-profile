# Changelog

All notable changes to Filament Complete User Profile are documented here.

The project follows Semantic Versioning.

## [2.0.0] - Unreleased

### Added

- Account navigation rendered inside a page header card, with breadcrumbs, avatar and
  native sub-navigation.
- An Account Security summary panel beside the Overview and Profile areas, listing
  authenticator app, email MFA, active session and API token state for the enabled
  features.
- `CompleteUserProfilePlugin::pageHeader()` to configure the header without registering
  a second plugin.
- A page header row in the `filament-complete-user-profile:check` diagnostics.
- `CompleteUserProfile::headerSchema()`, `getBreadcrumbs()` and
  `getAccountSecurityAsideComponent()`.
- The created-API-token modal now masks the token by default (showing a truncated
  preview), with a suffix action to reveal or hide it and a warning callout stating the
  token cannot be viewed again once the dialog closes. Copying always copies the full
  token, regardless of whether it is currently revealed.

### Changed

- The page heading, subheading and breadcrumbs follow the active account area.
- Content cards no longer repeat the area description, which now lives in the header.
- **Breaking:** `CompleteUserProfile::content()` now always returns a schema whose single
  top-level component is a `Grid`, not a `Section`. Code that read
  `$page->content($schema)->getComponents()[0]` and expected a `Section` must instead read
  the main content component out of the grid's own child schema.

### Removed

- `AccountNavigationLayout`, `CompleteUserProfilePlugin::navigation()` and
  `getNavigationLayout()`. There is one navigation layout.
- `AccountSection::icon()` and `getIcon()`. The account navigation has no icons.
- `CompleteUserProfile::getSubNavigationPosition()` and `getAccountItemIcon()`.

### Requirements

- Filament 5.8.1 or newer, below Filament 6.
- `mortalkiller/filament-page-header` 2.3.1 or newer.

### Upgrading from 1.x

1. Remove `->navigation(AccountNavigationLayout::Tabs)` or `::Sidebar` from your plugin
   configuration, and remove the `AccountNavigationLayout` import.
2. Remove `->icon(...)` from every custom `AccountSection`.
3. Raise Filament to 5.8.1 or newer.
4. If you already require `mortalkiller/filament-page-header`, raise it to `^2.3.1`.

## [1.0.0] - Unreleased

Initial stable release.

### Added

- Native Filament 5 account center with Overview, Profile and Security areas.
- Avatar, name, email and locale profile management.
- Configurable user-model or separate-table profile storage.
- Authenticator-app MFA with recovery codes using Filament's native MFA flow.
- Email MFA with queued notifications and resend cooldown.
- Browser session listing and revocation for Laravel database sessions.
- Laravel Sanctum API token management with explicit ability whitelists and expiration limits.
- Optional tenant-scoped API tokens with fail-closed context validation.
- Custom profile fields and save hooks.
- First-class custom account sections.
- Tabs and sidebar account navigation layouts.
- Installation diagnostics command.
- English, Portuguese, Spanish and French translations.
- Astro + Starlight public documentation.

### Requirements

- PHP 8.3 or newer within the package's declared PHP 8 major range.
- Laravel 13.
- Filament 5.7.6 or newer, below Filament 6.

Filament 5.7.6 is the minimum supported Filament release so version 1 does not install older Filament 5 versions affected by known MFA security advisories.
