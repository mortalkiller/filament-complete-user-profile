# Changelog

All notable changes to Filament Complete User Profile are documented here.

The project follows Semantic Versioning.

## [1.0.0] - Unreleased

Initial stable release.

This is the package's first public API. Development iterations completed before this release are part of the v1.0.0 baseline and are not treated as upgrade or breaking-change history.

### Added

- Native Filament 5 account center with Overview, Profile and Security areas.
- Avatar, name, email and locale profile management.
- Configurable user-model or separate-table profile storage.
- Authenticator-app MFA with recovery codes using Filament's native MFA flow.
- Email MFA with queued verification notifications and resend cooldown.
- Browser session listing and revocation for Laravel database sessions.
- Laravel Sanctum API token management with explicit ability whitelists and expiration limits.
- Optional tenant-scoped API tokens with fail-closed context validation.
- Custom profile fields and save hooks.
- First-class custom account sections.
- Account navigation rendered inside a page header card with breadcrumbs, avatar and native sub-navigation.
- Account Security summary beside the Overview and Profile areas, showing applicable MFA, session and API-token state.
- `CompleteUserProfilePlugin::pageHeader()` for per-panel page-header configuration.
- Page-header diagnostics in `filament-complete-user-profile:check`.
- `CompleteUserProfile::headerSchema()`, `getBreadcrumbs()` and `getAccountSecurityAsideComponent()`.
- Created API tokens displayed in a native read-only masked Filament input with copy, show and hide controls.
- English, Portuguese, Spanish and French translations.
- Astro + Starlight public documentation with release-tag publishing and versioned major channels.

### Requirements

- PHP `^8.3`.
- Laravel 13.
- Filament `>=5.8.3 <6.0.0`.
- `mortalkiller/filament-page-header` `^2.3.1`.

Filament 5.8.3 is the minimum supported Filament release. It remains above the earlier Filament 5 releases affected by the relevant MFA security advisories and includes the upstream Livewire `DataStore` singleton fix required by this package's supported integration.
