# Email MFA Cooldown and Verification Email Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a server-authoritative 60-second resend cooldown with live disabled-state countdown to Filament email MFA, and replace the minimal verification notification with a polished queued OTP email.

**Architecture:** Keep Filament's native email MFA provider semantics and provider ID, but register a package-owned subclass that centralizes resend cooldown state and swaps in a package notification. Reproduce only the smallest static setup action needed to expose cooldown-aware resend UI; customize challenge resend from the provider. Keep all public package DSL unchanged.

**Tech Stack:** PHP ^8.3, Laravel 13, Filament 5.7+, Livewire 4, Alpine via Filament attributes, Laravel Notifications/Markdown mail, RateLimiter, PHPUnit 12, PHPStan, Pint.

**Spec:** `docs/superpowers/specs/2026-09-15-email-mfa-cooldown-and-email-design.md`

## Global Constraints

- Public API remains `Security::emailAuthentication()`; no new package configuration method.
- Resend cooldown is exactly 60 seconds and is enforced server-side.
- Verification-code lifetime remains Filament's native default (currently 4 minutes).
- Provider ID remains `email_code`.
- Notification remains queued and uses the `mail` channel.
- New copy ships with translation parity for `en`, `pt`, `es`, and `fr`.
- No custom JavaScript build pipeline.
- Do not change authenticator-app MFA, sessions, API tokens, or application queue configuration.

---

### Task 1: Package email-authentication provider and server-side cooldown

**Files:**
- Create: `src/Security/EmailAuthentication/EmailAuthentication.php`
- Modify: `src/CompleteUserProfilePlugin.php`
- Test: `tests/Feature/EmailAuthenticationTest.php`

**Interfaces:**
- Produces: `getResendCooldownSeconds(): int`, `getResendAvailableIn(HasEmailAuthentication $user): int`, `canSendCode(HasEmailAuthentication $user): bool`, and cooldown-aware `sendCode(HasEmailAuthentication $user): bool`.
- Keeps Filament provider ID `email_code` and `EmailAuthentication` inheritance.

- [ ] **Step 1: Write failing provider tests**

Cover package-provider registration, first send, duplicate send rejection, decreasing remaining seconds, and resend after 60 seconds using Laravel's frozen/travelled clock. Fake notifications so no transport is required.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `vendor/bin/phpunit tests/Feature/EmailAuthenticationTest.php`

Expected: FAIL because the package provider/cooldown methods do not exist and the plugin still registers Filament's stock provider.

- [ ] **Step 3: Implement the minimal provider**

Create a subclass of Filament's native email provider. Use one deterministic per-user `RateLimiter` key, `availableIn()` for authoritative remaining seconds, and a single 60-second decay window. Accepted sends call the native code-generation/session-storage/notification semantics exactly once; rejected sends queue nothing.

- [ ] **Step 4: Register the package provider**

Update `CompleteUserProfilePlugin::register()` so `Security::emailAuthentication()` registers the package provider while `appAuthentication()` remains unchanged.

- [ ] **Step 5: Run focused tests and static analysis**

Run:

```bash
vendor/bin/phpunit tests/Feature/EmailAuthenticationTest.php
vendor/bin/phpstan analyse --memory-limit=1G
```

Expected: PASS / no errors.

- [ ] **Step 6: Commit**

```bash
git add src/Security/EmailAuthentication/EmailAuthentication.php src/CompleteUserProfilePlugin.php tests/Feature/EmailAuthenticationTest.php
git commit -m "feat: add email MFA resend cooldown"
```

---

### Task 2: Cooldown-aware setup and challenge resend UI

**Files:**
- Create: `src/Security/EmailAuthentication/Actions/SetUpEmailAuthenticationAction.php`
- Modify: `src/Security/EmailAuthentication/EmailAuthentication.php`
- Modify: `resources/lang/en/profile.php`
- Modify: `resources/lang/pt/profile.php`
- Modify: `resources/lang/es/profile.php`
- Modify: `resources/lang/fr/profile.php`
- Test: `tests/Feature/EmailAuthenticationTest.php`
- Test: `tests/Feature/TranslationsTest.php`

**Interfaces:**
- Consumes provider cooldown methods from Task 1.
- Produces setup/challenge resend actions whose disabled state and label are derived from server cooldown state.

- [ ] **Step 1: Write failing UI-schema tests**

Assert setup and challenge resend actions use the same provider cooldown, render a translated base label plus `(Ns)` while cooling down, are disabled while remaining seconds are positive, and become enabled when time reaches zero.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `vendor/bin/phpunit tests/Feature/EmailAuthenticationTest.php tests/Feature/TranslationsTest.php`

Expected: FAIL because Filament's stock setup/challenge actions do not expose the desired countdown state.

- [ ] **Step 3: Implement the smallest setup-action override**

Mirror only Filament's static setup action behavior that cannot be extended. Preserve native code validation, setup transaction, notifications, icons, and action rate limiting. Replace only initial-send/resend handling with the package provider's cooldown-aware methods.

- [ ] **Step 4: Customize challenge resend in the provider**

Override the challenge form schema only as needed to keep Filament's `OneTimeCodeInput` and verification rule while replacing the resend action with the same package cooldown behavior.

Use lightweight Alpine/Filament extra attributes or Livewire polling/state so the visible seconds tick down and the action becomes interactive at zero without a full page refresh. Backend `RateLimiter` state remains authoritative.

- [ ] **Step 5: Add translation keys in all four languages**

Add concise labels and success/cooldown copy under the package's security/email-authentication translation tree. Preserve exact key parity across `en`, `pt`, `es`, and `fr`.

- [ ] **Step 6: Run focused tests and quality checks**

Run:

```bash
vendor/bin/phpunit tests/Feature/EmailAuthenticationTest.php tests/Feature/TranslationsTest.php
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
```

Expected: PASS / no errors.

- [ ] **Step 7: Commit**

```bash
git add src/Security/EmailAuthentication resources/lang tests/Feature
git commit -m "feat: add email MFA resend countdown"
```

---

### Task 3: Polished queued OTP notification

**Files:**
- Create: `src/Security/EmailAuthentication/Notifications/VerifyEmailAuthentication.php`
- Create: `resources/views/emails/verify-email-authentication.blade.php`
- Modify: `src/Security/EmailAuthentication/EmailAuthentication.php`
- Modify: `resources/lang/en/profile.php`
- Modify: `resources/lang/pt/profile.php`
- Modify: `resources/lang/es/profile.php`
- Modify: `resources/lang/fr/profile.php`
- Test: `tests/Feature/EmailAuthenticationTest.php`
- Test: `tests/Feature/TranslationsTest.php`

**Interfaces:**
- Produces a Laravel queued notification using the `mail` channel and `config('app.name')` branding.
- Provider configures this notification as its code notification.

- [ ] **Step 1: Write failing notification tests**

Assert the notification implements `ShouldQueue`, routes through `mail`, renders app name, six-digit OTP, expiry copy, security warning, ignore guidance, and contains no CTA/button. Assert localized output for `en`, `pt`, `es`, and `fr`.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `vendor/bin/phpunit tests/Feature/EmailAuthenticationTest.php tests/Feature/TranslationsTest.php`

Expected: FAIL because the package notification/view do not exist.

- [ ] **Step 3: Implement notification and mail content**

Use Laravel's normal notification mail framework and a package Markdown/Blade content view rather than replacing the full mail layout. Visually emphasize the code, group it for readability without altering the underlying value, include expiry/security/ignore text, and use `config('app.name')`.

- [ ] **Step 4: Configure provider to use the package notification**

Set the package notification through Filament's `codeNotification()` extension point during provider construction/creation.

- [ ] **Step 5: Run focused tests and quality checks**

Run:

```bash
vendor/bin/phpunit tests/Feature/EmailAuthenticationTest.php tests/Feature/TranslationsTest.php
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
```

Expected: PASS / no errors.

- [ ] **Step 6: Commit**

```bash
git add src/Security/EmailAuthentication resources/views/emails resources/lang tests/Feature
git commit -m "feat: improve email MFA verification email"
```

---

### Task 4: Documentation and full regression verification

**Files:**
- Modify: `README.md`
- Test: full suite and CI workflows

**Interfaces:**
- Documents runtime requirement only; no public API changes.

- [ ] **Step 1: Update README**

Document the 60-second resend cooldown, Filament code lifetime, queued notification behavior, and requirement for a running worker when using `database`, Redis, or another asynchronous queue connection.

- [ ] **Step 2: Run full local verification**

Run:

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/phpunit
```

Expected: all commands exit 0.

- [ ] **Step 3: Commit documentation**

```bash
git add README.md
git commit -m "docs: document email MFA delivery behavior"
```

- [ ] **Step 4: Verify GitHub Actions matrix**

Confirm the branch's Tests workflow is green on PHP 8.3, 8.4, and 8.5, and Code quality is green on the same final commit.

- [ ] **Step 5: Confirm branch isolation**

Verify the work remains only on `feature/initial-implementation` and `main` has not moved as part of this implementation.
