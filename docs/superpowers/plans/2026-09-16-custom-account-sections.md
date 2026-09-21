# Custom Account Sections Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Laravel/Filament-style `AccountSection` extension point that lets host applications register custom navigable account areas using native Filament schemas without package-managed persistence.

**Architecture:** Keep package-owned `ProfileFeature` objects unchanged and store application-owned `AccountSection` objects separately on the plugin. `CompleteUserProfile` merges visible built-ins and custom sections only for navigation/rendering, preserving current feature responsibilities while allowing custom schema content in Tabs and Sidebar layouts.

**Tech Stack:** PHP 8.3+, Laravel 13, Filament 5.7+, Livewire 4, PHPUnit 12, PHPStan 2, Laravel Pint.

**Spec:** `docs/superpowers/specs/2026-09-16-custom-account-sections-design.md`

## Global Constraints

- Public registration API is singular: `CompleteUserProfilePlugin::section(AccountSection $section)`.
- `AccountSection` exposes only `make()`, `label()`, `icon()`, `description()`, `sort()`, `visible()` and `schema()` configuration in this version.
- Custom sections never automatically persist application-domain data.
- Built-in IDs `overview`, `profile`, `security`, `sessions`, and `api-tokens` are reserved.
- Custom IDs are lowercase kebab-case.
- No bulk `sections()` method, badges, navigation groups, persistence callbacks, view publishing, or feature rewrites.
- Existing package behaviour must remain unchanged when no custom sections are registered.
- Verify PHP 8.3, 8.4 and 8.5 plus Code quality before completion.

---

### Task 1: Lock `AccountSection` public API with RED unit tests

**Files:**
- Create: `tests/Unit/AccountSectionTest.php`
- Create later: `src/AccountSection.php`

**Interfaces:**
- Produces desired public API: `AccountSection::make(string $id): static`, `label(string|Closure): static`, `icon(string|BackedEnum|null): static`, `description(string|Closure|null): static`, `sort(int): static`, `visible(bool|Closure = true): static`, `schema(array|Closure): static`.
- Produces read methods needed by rendering: `getId(): string`, `getLabel(): string`, `getIcon(): string|BackedEnum|null`, `getDescription(): ?string`, `getSort(): int`, `isVisible(): bool`, `getSchema(): array`.

- [ ] **Step 1: Write failing API/default tests**

Create tests that instantiate `AccountSection::make('connected-accounts')`, assert the generated label is `Connected Accounts`, icon/description are null, sort is `100`, visibility is true, schema is empty, and every fluent setter returns the same instance.

- [ ] **Step 2: Write failing validation/schema tests**

Assert malformed IDs such as `Connected Accounts`, `connected_accounts`, and an empty string throw `InvalidArgumentException`. Assert `schema()` accepts arrays and closures returning Filament `Component` instances, while non-array closure results and non-component entries throw `LogicException`.

- [ ] **Step 3: Push tests and verify RED**

Expected result: PHPUnit fails because `Mortalkiller\FilamentCompleteUserProfile\AccountSection` does not exist. Pint/PHPStan may also report the missing class; this is the expected RED reason.

- [ ] **Step 4: Implement minimal `AccountSection`**

Create `src/AccountSection.php` using `Filament\Support\Concerns\EvaluatesClosures`. Validate IDs in construction, use `Str::headline()` for default labels, validate schema results, and keep all exception messages in English.

- [ ] **Step 5: Run focused tests until GREEN**

Run `vendor/bin/phpunit tests/Unit/AccountSectionTest.php`, then Pint and PHPStan.

---

### Task 2: Register custom sections on the plugin with RED tests

**Files:**
- Create: `tests/Feature/CustomAccountSectionsTest.php`
- Modify: `src/CompleteUserProfilePlugin.php`

**Interfaces:**
- Consumes: `AccountSection` from Task 1.
- Produces: `section(AccountSection $section): static`, `getSections(): array<string, AccountSection>`, `getVisibleSections(): array<string, AccountSection>`.

- [ ] **Step 1: Write failing registration tests**

Test chained `section()` calls, retrieval by ID, conditional visibility, default ordering, custom sort ordering, duplicate custom IDs, and collisions with all built-in IDs.

- [ ] **Step 2: Verify RED**

Expected result: failures because `CompleteUserProfilePlugin::section()`, `getSections()` and `getVisibleSections()` do not exist.

- [ ] **Step 3: Implement plugin registration**

Add a separate `sections` collection. `section()` rejects IDs already used by built-in features or custom sections using `LogicException`. `getVisibleSections()` filters `isVisible()` and sorts ascending by `getSort()` while preserving registration order for equal sort values.

- [ ] **Step 4: Verify focused tests GREEN**

Run `vendor/bin/phpunit tests/Feature/CustomAccountSectionsTest.php tests/Unit/AccountSectionTest.php`, then Pint and PHPStan.

---

### Task 3: Integrate custom sections into Sidebar and Tabs with RED feature tests

**Files:**
- Modify: `tests/Feature/CustomAccountSectionsTest.php`
- Modify: `tests/Feature/CompleteUserProfilePageTest.php`
- Modify: `src/Pages/CompleteUserProfile.php`

**Interfaces:**
- Consumes plugin `getVisibleFeatures()` and `getVisibleSections()`.
- Produces an internal ordered list of `ProfileFeature|AccountSection` used by both navigation layouts.

- [ ] **Step 1: Write failing combined-order/sidebar tests**

Register `AccountSection::make('preferences')->label('Preferences')->sort(25)` with Sidebar navigation. Assert labels are `Overview`, `Profile`, `Preferences`, `Security`; the custom URL contains `section=preferences`; setting page state to `preferences` makes only it active; an invisible custom section is omitted.

- [ ] **Step 2: Write failing fallback/render tests**

Assert an invalid or invisible selected custom section falls back to the first visible item. Assert a custom schema component is present in page content and a configured Heroicon can be read through the native navigation item/tab configuration path.

- [ ] **Step 3: Verify RED**

Expected result: custom sections are absent because the page currently renders only built-in `ProfileFeature` instances and dispatches content through a hardcoded feature-ID match.

- [ ] **Step 4: Implement merged account-item rendering**

Update `CompleteUserProfile` to merge visible built-ins and custom sections, sort the combined sequence stably by sort value, and use helpers for item ID, label, icon and content. Keep the existing built-in feature renderer unchanged. Render custom content using native `Section::make(...)->description(...)->schema(...)`. Apply custom icons to native `NavigationItem` and `Tabs\Tab` only when configured.

- [ ] **Step 5: Verify focused page tests GREEN**

Run `vendor/bin/phpunit tests/Feature/CustomAccountSectionsTest.php tests/Feature/CompleteUserProfilePageTest.php`, then Pint and PHPStan.

---

### Task 4: Protect the canonical API and document it

**Files:**
- Modify: `tests/Feature/PublicApiTest.php`
- Modify: `README.md`

**Interfaces:**
- Documents the same API implemented in Tasks 1–3; no aliases are introduced.

- [ ] **Step 1: Add public-API/documentation assertions**

Assert `CompleteUserProfilePlugin::section()` exists, `sections()` does not exist, `AccountSection` exposes the canonical fluent methods, and README contains `AccountSection::make(` plus an explicit statement that custom sections do not automatically persist domain data.

- [ ] **Step 2: Verify RED for README assertions**

Expected result: documentation assertions fail because the README does not yet document custom sections.

- [ ] **Step 3: Update README**

Add `Custom account sections` after `Customize Profile fields`. Show a minimal section, full fluent configuration, a Livewire schema example, reserved IDs, Tabs/Sidebar compatibility, and the persistence boundary. Explain when to use `Profile::fields()` versus `AccountSection`.

- [ ] **Step 4: Verify focused documentation/API tests GREEN**

Run `vendor/bin/phpunit tests/Feature/PublicApiTest.php tests/Feature/CustomAccountSectionsTest.php`.

---

### Task 5: Full verification

**Files:**
- No production changes unless verification exposes a regression that is first reproduced by a failing test.

- [ ] **Step 1: Run local/package checks where available**

Run `composer validate --strict`, `composer lint`, `composer analyse`, and `composer test`.

- [ ] **Step 2: Verify GitHub Actions matrix**

Confirm Tests succeeds on PHP 8.3, 8.4 and 8.5 for the final commit.

- [ ] **Step 3: Verify Code quality workflow**

Confirm Composer validation, Pint, PHPStan, tracked PHP syntax and distribution archive verification all succeed.

- [ ] **Step 4: Review final diff**

Confirm changes are limited to the new extension point, tests, README and approved design/plan documentation; `main` remains untouched.