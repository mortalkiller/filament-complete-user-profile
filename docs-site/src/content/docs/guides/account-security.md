---
title: Account Security summary
description: The security summary card shown beside the Overview and Profile areas.
---

Beside the Overview and Profile areas, the account page can render an "Account Security"
summary card next to the main content. It exists to give the user a one-glance view of
their security posture without navigating into each area individually.

## Where it renders

The card renders only when the active account area is Overview or Profile. Every other
area — Security, Sessions, API Tokens and custom sections — renders full width, without
the card.

## Rows

The card lists up to four rows. Each row is gated independently by its own feature and
state, so the card reflects exactly what is enabled and configured for the current user:

| Row | Appears when | State shown |
| --- | --- | --- |
| Authenticator app | The Security feature is enabled and app authentication is configured (`Security::hasAppAuthentication()`) | "Enabled" when the user has a stored app-authentication secret, otherwise "Not configured" |
| Email MFA | The Security feature is enabled and email authentication is configured (`Security::hasEmailAuthentication()`) | "Enabled" when the user's `hasEmailAuthentication()` returns `true`, otherwise "Not configured" |
| Active sessions | The Sessions feature is enabled **and** the session store reports itself supported | The user's active session count, using the singular or plural translation form |
| Personal access tokens | The API Tokens feature is enabled and the user model exposes a `tokens()` relation | The user's token count, using the singular or plural translation form |

Each bordered row is a full-row link to the account area it summarizes, with a leading icon, label, state and trailing chevron. Links support keyboard navigation and Filament SPA navigation. Only the two MFA rows have a status dot: green means enabled, neutral means not configured. Session and token counts have no dot and do not imply that an account is secure.

The card has a neutral description, moves below the main content on narrow screens and supports dark mode. Its scoped CSS is registered with Filament and loads in the document head. Run `php artisan filament:assets` after installing or updating the package; no custom theme rebuild is needed.

The card heading and description inherit the active Filament theme's typography. The compact row layout does not override the section header's font size, line height or weight.

## The sessions row and unsupported session stores

The Sessions area itself can be enabled while its underlying session store is unusable —
for example, the application uses a non-database session driver, or the `sessions` table
was never migrated. In that situation `SessionStore::isSupported()` returns `false`.

Rather than show a misleading "0 active sessions" next to a Sessions area that separately
reports the store as unsupported, the summary card omits the sessions row entirely when
the store is unsupported. The other rows are unaffected — they are gated independently.

See [Browser sessions](../sessions/) for the session store's own requirements.

## When the card does not render

If none of the four rows apply — every optional security feature is disabled, or none of
their requirements are met for the current user — the card is not rendered at all, and the
main content column takes the full width instead of sharing space with an empty card.

## Customizing the card

The card is built by a single, overridable method:

```php
protected function getAccountSecurityAsideComponent(): ?Component
```

on `Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile`. It is `protected`,
so extend the page class and override the method to add, remove or reorder rows, or to
replace the card with different content entirely. Returning `null` suppresses the card just
as the built-in "no rows apply" case does.

```php
use Filament\Schemas\Components\Component;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile as BaseCompleteUserProfile;

class CompleteUserProfile extends BaseCompleteUserProfile
{
    protected function getAccountSecurityAsideComponent(): ?Component
    {
        // Call parent::getAccountSecurityAsideComponent() to keep the
        // built-in rows, or build a Section from scratch.
        return parent::getAccountSecurityAsideComponent();
    }
}
```

See [Public API stability](../../api/extension-points/#public-api-stability) for how the
package treats overridable page internals versus its documented contracts.
