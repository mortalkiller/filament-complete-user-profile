# Roadmap

This document records ideas being considered for future releases of Filament Complete User Profile.

Items listed here are exploratory. They are not release commitments and may change or be removed as Laravel and Filament evolve.

## Passkeys / WebAuthn

### Current decision

Keep the current authentication architecture unchanged for now:

- Authenticator-app / TOTP MFA uses Filament's native `AppAuthentication` provider.
- Email-code MFA uses Filament's native `EmailAuthentication` provider.
- Do not add Laravel Fortify as a package dependency.

### Future direction

Evaluate first-party Laravel Passkeys / WebAuthn support as an optional security capability.

The preferred architecture is to keep Filament responsible for the panel authentication flow and use Laravel's official passkey implementation only for WebAuthn ceremonies and credential storage.

A future integration may expose an API similar to:

```php
CompleteUserProfilePlugin::make()
    ->security(fn (Security $security): Security => $security
        ->appAuthentication()
        ->emailAuthentication()
        ->passkeys());
```

The exact public API must be designed against the Laravel and Filament versions current at implementation time.

### Possible phases

#### 1. Passkeys as MFA

Investigate a Filament `MultiFactorAuthenticationProvider` adapter backed by Laravel Passkeys.

Goals:

- Allow a passkey to be used as an additional MFA method alongside authenticator-app and email MFA.
- Reuse Filament's native MFA challenge flow rather than introducing a parallel authentication system.
- Keep WebAuthn cryptography and protocol handling outside this package.

#### 2. Passkey management

Add passkey management to the Security area so a user can register, name, inspect, and remove credentials such as:

- Touch ID / Face ID devices
- Windows Hello
- Android passkeys
- Hardware security keys such as YubiKey

Credential storage should remain owned by the Laravel passkey implementation, not by Filament Complete User Profile.

#### 3. Passwordless login

Consider passwordless sign-in only after Passkey MFA and credential management are stable.

This is a separate concern because it changes the primary Filament login flow, not only account security settings.

Potential functionality may include:

- "Sign in with a passkey"
- WebAuthn autofill / conditional UI where supported
- Coexistence with password and social login

#### 4. Passkey reauthentication

Evaluate a passkey-based implementation of the package's `Reauthentication` contract.

This could allow sensitive actions such as changing a password, creating API tokens, revoking sessions, or disabling MFA to confirm the user's identity with a passkey instead of requiring a local password.

### Dependency strategy

Passkeys should remain optional.

If Laravel Passkeys is adopted, it should not become a mandatory runtime dependency for applications that do not enable the feature. Prefer an optional dependency / Composer suggestion plus development-time coverage.

The diagnostics command should fail clearly when Passkeys are enabled but their required infrastructure is unavailable.

### Multi-panel and multi-tenant considerations

Before implementation, explicitly validate WebAuthn relying-party and origin behaviour for applications that use multiple Filament panels, custom domains, or tenant subdomains.

Areas that require design and tests include:

- Relying Party ID (`rpId`)
- Allowed origins
- Central-domain versus tenant-subdomain login
- Whether one credential should be valid across supported subdomains
- Custom tenant domains
- Local-development origins

The package must not guess these values. They belong to the host application's authentication / WebAuthn configuration.

### References to review before implementation

When this roadmap item is picked up, re-evaluate the current versions and documentation of:

- Laravel Passkeys / WebAuthn support
- Filament's `MultiFactorAuthenticationProvider` extension point
- Filament's native authenticator-app and email MFA providers
- Existing Filament 5 passkey integrations as implementation references

Do not base the implementation solely on the API shape described in this roadmap, since both Laravel Passkeys and Filament may evolve before this work begins.
