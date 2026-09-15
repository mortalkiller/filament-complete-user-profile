# Email MFA Cooldown and Verification Email — Design Specification

## Goal

Improve the Filament email multi-factor authentication experience by making resend throttling visible and predictable to the user and by replacing the minimal default verification email with a polished, reusable OTP email while preserving Filament's native MFA flow and verification semantics.

## Scope

This change applies only when `Security::emailAuthentication()` is enabled.

It must not change:

- the public plugin API;
- the `HasEmailAuthentication` model contract;
- package email-MFA storage behavior;
- Filament's code verification semantics;
- the existing 4-minute verification-code lifetime unless Filament changes its native default in a future supported release;
- authenticator-app MFA;
- sessions or API tokens.

## Architectural approach

Do not fork Filament's MFA subsystem.

Create a package-owned email authentication provider that extends Filament's native `Filament\Auth\MultiFactor\Email\EmailAuthentication` and reuses its code generation, session storage, verification, challenge lifecycle, and provider ID.

Override only the behavior required for the improved UX:

1. resend cooldown state and resend actions;
2. the notification used to deliver the code;
3. the setup-management action where Filament's stock resend link does not expose a live cooldown.

The package plugin continues to register the provider through the existing API:

```php
CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->emailAuthentication());
```

Consumers do not need a new configuration method for the default behavior.

## Resend cooldown

### Default duration

The resend cooldown is 60 seconds.

This is separate from the verification code lifetime. A code may remain valid for 4 minutes while the user can request another code after 60 seconds.

### Backend enforcement

Cooldown must be enforced server-side, not only through disabled frontend state.

Use Laravel's `RateLimiter` with a deterministic per-user key dedicated to email-MFA sending. The same cooldown source must be used by:

- initial setup code sending;
- setup modal resend;
- login/challenge resend.

A user must not be able to bypass the cooldown by manually triggering the Livewire action, refreshing markup, or modifying browser state.

The provider should expose small methods that make cooldown state reusable by UI code, conceptually:

```php
public function getResendCooldownSeconds(): int;
public function getResendAvailableIn(HasEmailAuthentication $user): int;
public function canSendCode(HasEmailAuthentication $user): bool;
```

Exact internal names may vary if a clearer implementation emerges, but cooldown computation must have one source of truth.

### Initial send

Opening the setup flow or reaching the MFA challenge sends the first code immediately when allowed and starts the 60-second cooldown.

If the user enters a flow while a cooldown already exists, do not create a duplicate email merely because the UI mounted again.

### Resend control UX

While cooldown is active, the resend action is disabled and shows the remaining seconds.

Portuguese example:

```text
Enviar um novo código por email (60s)
Enviar um novo código por email (59s)
...
Enviar um novo código por email (1s)
```

At zero it automatically becomes:

```text
Enviar um novo código por email
```

and becomes interactive again without requiring a full page refresh.

The countdown is presentation state derived from the server-authoritative cooldown expiry. It must not be the security control itself.

Use lightweight Filament/Livewire/Alpine integration rather than introducing a custom JavaScript build pipeline.

### Successful resend

On successful resend:

- generate and store a new code through the native Filament provider behavior;
- restart the 60-second cooldown;
- update the visible countdown immediately;
- show a concise success notification using package translations.

### Cooldown rejection

If the backend rejects a resend because the cooldown is still active, no email is queued and the UI synchronizes to the authoritative remaining time instead of showing a generic error.

## Verification email

### Notification

Provide a package-owned queued Laravel notification for email MFA.

It remains queueable, matching Filament's current behavior, and uses the `mail` notification channel.

The provider registers this package notification through Filament's existing `codeNotification()` extension point or equivalent constructor/setup configuration.

### Email content

The message should follow a conventional one-time-code email hierarchy:

1. application identity / name;
2. clear heading such as "Confirme a sua identidade";
3. one short explanation;
4. the six-digit code as the visual focal point;
5. code-expiry information;
6. security warning not to share the code;
7. "ignore this email" guidance when the recipient did not request it;
8. small application-name footer.

Do not include a call-to-action button. The intended action is entering the code into the current browser session.

### Branding

The package must remain application-agnostic.

Use:

```php
config('app.name')
```

for the application name.

Do not hardcode Pressiu or package branding.

Use Laravel's normal mail notification layout where practical so the host application's mail theme/logo customization continues to work. A package Blade mail view may be used for the content body, but it must not replace the host application's entire mail framework unnecessarily.

### Code presentation

The verification code must be highly visible, centered, and easy to scan or copy.

Display spacing may visually group the six digits, for example:

```text
483 921
```

but the underlying code remains the same six-digit value.

Avoid decorative complexity and images that may be blocked by email clients.

### Localization

Ship all new copy in the package's existing languages:

- English (`en`)
- Portuguese (`pt`)
- Spanish (`es`)
- French (`fr`)

Translation parity tests must continue to guarantee that all shipped locales contain the same keys.

## Proposed code boundaries

Prefer focused package-owned classes under a dedicated email-authentication namespace, conceptually:

```text
src/Security/EmailAuthentication/
├── EmailAuthentication.php
├── Actions/
│   └── SetUpEmailAuthenticationAction.php
└── Notifications/
    └── VerifyEmailAuthentication.php
```

If the challenge resend UI can be cleanly customized inside the provider without an extra class, do not introduce unnecessary classes.

Mail content may live under:

```text
resources/views/emails/verify-email-authentication.blade.php
```

or use Laravel `MailMessage` components if that produces the required layout without a dedicated view.

Keep the implementation minimal and avoid copying whole Filament classes unless no extension point exists for the specific UI being replaced.

## Filament compatibility

The custom provider must retain the same provider ID (`email_code`) and remain compatible with Filament's native multi-factor management and login challenge infrastructure.

The implementation must be based on the supported Filament 5 API currently installed by the package and covered by tests so upstream changes are detected.

Where a Filament class must be reproduced because it is static/non-extensible, copy only the smallest required behavior and document why the override exists.

## Queue behavior

The verification notification remains queued.

Documentation should explicitly mention that email MFA requires a working queue worker when the application uses an asynchronous queue connection.

Do not change the application's queue connection or force synchronous notification delivery.

## Error handling

- Missing `HasEmailAuthentication` contract continues to produce the existing actionable readiness error.
- Missing notification support continues to fail diagnostics.
- Mail transport/queue failures are allowed to surface through Laravel's normal failed-job/logging infrastructure.
- Cooldown rejection is treated as expected UX state, not an application error.
- A resend must never create more than one code notification per accepted resend action.

## Testing strategy

Use TDD.

Tests must cover at least:

1. email authentication still registers under provider ID `email_code`;
2. the package provider remains compatible with Filament's `EmailAuthentication` type/contract expectations;
3. first send succeeds and creates a 60-second cooldown;
4. resend during cooldown is rejected server-side;
5. remaining cooldown seconds decrease correctly;
6. resend after cooldown succeeds and restarts the cooldown;
7. setup modal resend uses the same cooldown source;
8. challenge resend uses the same cooldown source;
9. UI label contains the remaining seconds while disabled;
10. UI becomes enabled when cooldown reaches zero;
11. notification remains queued and uses the mail channel;
12. email content contains the OTP, application name, expiry text, security warning, and no CTA button;
13. email content is translated in `en`, `pt`, `es`, and `fr`;
14. translation parity remains green;
15. existing authenticator-app MFA tests remain unchanged and green;
16. full package matrix passes on PHP 8.3, 8.4, and 8.5;
17. Pint, PHPStan, tracked PHP syntax, and code-quality workflow pass.

Tests involving time/cooldown should freeze or travel Laravel's clock rather than sleep.

## Documentation

Update `README.md` to explain:

- email MFA uses a 60-second resend cooldown;
- codes expire according to the provider's code lifetime;
- verification emails are queued;
- applications using `QUEUE_CONNECTION=database`, Redis, or another async driver need a running queue worker.

No new public API documentation is required because `->emailAuthentication()` remains unchanged.

## Out of scope

This work does not:

- make the 60-second cooldown publicly configurable yet;
- make the code expiry publicly configurable through the package DSL;
- replace Laravel's mail transport configuration;
- implement delivery-status tracking;
- add SMS, WhatsApp, or other OTP channels;
- redesign authenticator-app MFA;
- add Passkeys/WebAuthn;
- add custom branding configuration beyond Laravel's existing app/mail customization mechanisms.

## Result

Email MFA should feel like a conventional polished OTP flow while remaining native to the existing Laravel and Filament architecture:

```text
Request code
    ↓
Queued verification email
    ↓
60-second resend cooldown
    ↓
Disabled resend + live remaining seconds
    ↓
Resend becomes available automatically
```

The backend remains authoritative for all resend decisions, and the package continues to expose the same simple public API.