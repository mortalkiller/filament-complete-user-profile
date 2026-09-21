# Public API Simplification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace duplicate pre-release configuration methods with one canonical Laravel/Filament-style API without changing behaviour.

**Architecture:** Keep the current feature objects and runtime integrations. Simplify only the public DSL: plugin-root methods select/configure features; feature-specific options stay inside feature callbacks. Rename `navigationLayout()` to `navigation()` and authenticator-app MFA configuration to `appAuthentication()`, remove duplicate aliases, then update internal consumers, tests and docs.

**Tech Stack:** PHP 8.3+, Laravel 13, Filament 5, Livewire 4, PHPUnit 12, PHPStan 2, Laravel Pint.

**Spec:** `docs/superpowers/specs/2026-09-15-api-simplification-design.md`

## Global Constraints

- Preserve all current defaults and security behaviour.
- Do not keep aliases/deprecations for removed pre-release methods.
- Do not rename MFA storage contracts/traits in this refactor.
- Do not add Passkeys or Fortify.
- Verify PHP 8.3, 8.4 and 8.5 plus Code quality before completion.

---

### Task 1: Lock the canonical API with RED tests

**Files:**
- Create: `tests/Feature/PublicApiTest.php`
- Modify later: existing feature tests that use old names.

- [ ] Add reflection-based tests requiring `navigation()`, `appAuthentication()`, `hasAppAuthentication()`, and `getAppAuthenticationRequirementIssue()`.
- [ ] Assert `navigationLayout()`, all `*With()` methods, root-level MFA shortcuts, and `Security::multiFactorAuthentication()` are absent.
- [ ] Push and confirm PHPUnit is RED for the expected API-surface assertions while Pint/PHPStan remain clean.

### Task 2: Implement the new API and migrate internal consumers

**Files:**
- Modify: `src/CompleteUserProfilePlugin.php`
- Modify: `src/Features/Security.php`
- Modify: `src/Pages/CompleteUserProfile.php`
- Modify: `src/Commands/CheckCompleteUserProfile.php`
- Modify: tests that use the old names.

- [ ] Rename `navigationLayout()` to `navigation()` while keeping `getNavigationLayout()`.
- [ ] Remove `overviewWith()`, `profileWith()`, `securityWith()`, `sessionsWith()`, `apiTokensWith()` and `configureTypedFeature()`.
- [ ] Remove root-level `multiFactorAuthentication()` and `emailAuthentication()` shortcuts.
- [ ] Rename Security app-MFA state/config/query/requirement methods to `appAuthentication*`.
- [ ] Update provider registration, page rendering and diagnostics to the new internal names.
- [ ] Update tests to configure MFA only through `security(...)`.
- [ ] Run focused tests, Pint and PHPStan until green.

### Task 3: Update documentation

**Files:**
- Modify: `README.md`
- Modify: `docs/roadmap.md`
- Modify: `docs/superpowers/specs/2026-09-15-filament-complete-user-profile-design.md`

- [ ] Replace old examples with `navigation()` and `appAuthentication()`.
- [ ] Ensure package docs no longer advertise removed methods.
- [ ] Keep Passkeys roadmap exploratory and otherwise unchanged.

### Task 4: Full verification

- [ ] Run Composer validation, Pint, PHPStan and full PHPUnit suite.
- [ ] Verify GitHub Actions Tests matrix on PHP 8.3/8.4/8.5.
- [ ] Verify dedicated Code quality workflow.
- [ ] Confirm `main` was not modified.
