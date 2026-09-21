---
title: Security
description: Report vulnerabilities privately and configure security-sensitive features conservatively.
---

Do not report suspected vulnerabilities in a public GitHub issue.

Use the private reporting process described in the repository's [SECURITY.md](https://github.com/mortalkiller/filament-complete-user-profile/blob/main/SECURITY.md).

## Sensitive data

Examples and bug reports should remove:

- API tokens and plaintext token values;
- MFA secrets and recovery codes;
- session identifiers;
- credentials;
- real customer or user data.

## Security-sensitive integrations

Pay particular attention when enabling:

- custom authentication callbacks alongside Filament MFA;
- browser-session revocation;
- Sanctum token creation;
- tenant-scoped API tokens;
- custom `Reauthentication`, `SessionStore` or `TokenContextResolver` bindings.

Tenant-scoped tokens are designed to fail closed when the resolver, active context or token context is missing or mismatched. Keep `EnsureTokenContext` after `auth:sanctum` on tenant-sensitive API routes.

The package documentation describes observed implementation behavior and requirements; it does not replace an application-specific security review.
