# Security Policy

## Supported versions

Until the first stable release is published, security fixes are maintained on the repository's default development line. After a stable major is released, this section will list the supported package lines explicitly.

Use the latest available patch release for supported versions.

## Reporting a vulnerability

Please [report a vulnerability privately on GitHub](https://github.com/mortalkiller/filament-complete-user-profile/security/advisories/new). Do not disclose an unpatched vulnerability in a public issue or pull request.

Include:

- Package, PHP, Laravel and Filament versions.
- A minimal reproduction and the affected feature or configuration.
- The expected behavior, actual behavior and likely impact.
- Whether the issue affects profile data, MFA, browser sessions, API tokens or tenant isolation.
- Any prerequisites needed to reproduce the issue.

Use fictional data and remove credentials, tokens, recovery codes, session identifiers and personal information. Ordinary bugs and feature requests belong in [GitHub Issues](https://github.com/mortalkiller/filament-complete-user-profile/issues).

## Response and disclosure

The maintainer reviews reports on a best-effort basis; there is no guaranteed response or resolution deadline. Follow-up, reproduction and remediation discussions take place in the private report. For confirmed vulnerabilities, the maintainer coordinates a fix and public disclosure, including release notes or a security advisory as appropriate. Reporter credit is subject to the reporter's consent.

## Package boundary

This package provides account/profile UI and supporting integrations for Filament. The consuming application remains responsible for its authentication architecture, authorization rules, tenant resolution, API middleware and deployment security.

When tenant-scoped API tokens are enabled, the package intentionally fails closed when the token context cannot be resolved or does not match the active context. Applications must keep that middleware in the protected API path and must not replace it with UI-only tenant filtering.

MFA uses Filament's native authenticator-app contracts. Custom authentication or social-login flows must not bypass Filament's MFA challenge.
