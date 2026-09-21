# Changelog

All notable changes to Filament Complete User Profile are documented here.

The project follows Semantic Versioning.

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
