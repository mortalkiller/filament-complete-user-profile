---
title: Roadmap
description: Exploratory future directions for Filament Complete User Profile.
---

Roadmap items are exploratory and are not release commitments.

## Passkeys / WebAuthn

The current package keeps its existing authentication architecture:

- authenticator-app/TOTP MFA uses Filament's native `AppAuthentication`;
- email-code MFA uses Filament's native email authentication;
- Laravel Fortify is not a package dependency.

A future version may evaluate first-party Laravel Passkeys/WebAuthn support as an optional capability.

Possible phases include:

1. passkeys as an additional Filament MFA provider;
2. passkey credential management inside the Security area;
3. passwordless login only after the MFA integration is stable;
4. a passkey-based implementation of the package `Reauthentication` contract.

Passkeys should remain optional rather than becoming a dependency for applications that do not enable them.

Multi-panel, custom-domain and tenant-subdomain installations would need explicit design around WebAuthn relying-party IDs and allowed origins. The package should not guess those host-application values.

The source roadmap is maintained in `docs/roadmap.md` in the repository.
