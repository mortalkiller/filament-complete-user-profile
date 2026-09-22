---
title: Filament Complete User Profile
description: A modular account center for Filament 5 and Laravel 13.
template: splash
editUrl: false
hero:
  tagline: Profile management, MFA, browser sessions and API tokens in one native Filament account experience.
  actions:
    - text: Get started
      link: /filament-complete-user-profile/getting-started/installation/
      icon: right-arrow
    - text: API reference
      link: /filament-complete-user-profile/api/
      variant: minimal
    - text: Pedro Monteiro
      link: https://pedromonteiro.dev
      icon: external
      variant: minimal
---

Filament Complete User Profile replaces the basic profile screen with a normal Filament panel page and lets each panel opt into the account capabilities it needs.

## Why this package

Real account areas often grow beyond name, email and password changes. MFA, active sessions, API tokens, locale and application-specific account tools can otherwise become separate pages with different navigation and interaction patterns.

This package keeps those concerns in one native Filament account experience while leaving domain data in the consuming application. Add normal fields to the Profile form, or compose first-class custom sections from Filament schema components, Livewire components, application services and Eloquent relationships.

![Filament Complete User Profile account center](/filament-complete-user-profile/filament-complete-user-profile.png)

## Highlights

- Native Filament panel page, tabs or sidebar navigation.
- Profile fields for avatar, name, email and locale.
- Application-owned profile fields saved through the existing Profile form.
- First-class custom account sections backed by services, settings packages or Eloquent relationships.
- Authenticator-app and email multi-factor authentication.
- Database-backed browser session management.
- Laravel Sanctum API token management.
- Optional tenant-scoped API tokens with fail-closed context validation.
- Configurable user-table or separate profile storage.
- Installation diagnostics with actionable PASS, INFO and FAIL results.

## Start here

Install the package and register `CompleteUserProfilePlugin::make()` on the Filament panels where the account center should be available.

The [installation guide](/filament-complete-user-profile/getting-started/installation/) covers the first setup. Use the [configuration guide](/filament-complete-user-profile/getting-started/configuration/) for the package defaults, then move to the task-oriented guides for optional capabilities.

For exact methods, arguments and extension contracts, use the [API reference](/filament-complete-user-profile/api/).
