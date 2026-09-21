---
title: API Reference
description: Reference for the public Filament Complete User Profile API.
---

Use the guides for task-oriented setup. This section documents the package's public configuration surface and extension contracts.

## Public API

| API | Purpose |
| --- | --- |
| [`CompleteUserProfilePlugin`](./complete-user-profile-plugin/) | Register features, navigation and custom sections per Filament panel. |
| [`AccountSection`](./account-section/) | Define a custom account navigation area and native Filament schema content. |
| [Profile](./profile/) | Configure built-in and custom profile fields and save hooks. |
| [Security](./security/) | Configure password, authenticator-app MFA and email MFA. |
| [Sessions](./sessions/) | Browser-session feature state and infrastructure requirements. |
| [API Tokens](./api-tokens/) | Sanctum abilities, expiration and tenant-scoped token settings. |
| [Contracts](./contracts/) | Replaceable profile storage, reauthentication, session and token-context abstractions. |
| [Concerns](./concerns/) | MFA storage adapter traits for authenticatable models. |
| [Configuration](./configuration/) | Structural package config keys and defaults. |
| [Extension points](./extension-points/) | Container bindings and application-owned integration points. |

## Fluent configuration conventions

Feature setters generally return the same instance. Plugin feature methods accept a boolean or a Closure.

```php
CompleteUserProfilePlugin::make()
    ->profile(fn (Profile $profile): Profile => $profile
        ->avatar(false))
    ->security(fn (Security $security): Security => $security
        ->appAuthentication())
    ->sessions();
```

The plugin belongs to a Filament panel, so its feature configuration is naturally per-panel. Structural storage configuration remains global package config.
