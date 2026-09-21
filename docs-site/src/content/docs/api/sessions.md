---
title: Sessions feature
description: Reference for browser-session feature state and support checks.
---

Namespace:

```php
Mortalkiller\FilamentCompleteUserProfile\Features\Sessions
```

The Sessions feature extends `AbstractFeature`, has default sort `40`, and is disabled by default by the plugin.

## Feature ID

```php
getId(): string // "sessions"
```

## Requirement check

```php
getRequirementIssue(SessionStore $store): ?string
```

When the feature is disabled, this returns `null`.

When enabled, it delegates to the configured `SessionStore::getUnsupportedReason()`. The built-in `DatabaseSessionStore` is intended for Laravel's database session driver.

## Common feature methods

Because Sessions extends `AbstractFeature`, it also supports:

```php
enabled(bool|Closure $condition = true): static
visible(bool|Closure $condition = true): static
sort(int $sort): static
isEnabled(): bool
isVisible(): bool
getSort(): int
```

Use `CompleteUserProfilePlugin::sessions()` for normal panel configuration.
