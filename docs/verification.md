# Verification record

## Package Standard v2 migration

The current `1.x` line adopts MortalKiller Package Standard v2 as a maintainer/tooling migration without changing the package major or intentionally changing its public runtime API.

Current CI adds Larastan level 8, strict randomized PHPUnit behavior, Zizmor, Template v2 reusable workflows and root Playwright Dependabot coverage. Exact migration evidence is recorded in the migration issue/PR and merge-commit workflows.

The historical release-readiness evidence below is preserved as history. References there to raw PHPStan, `main`, earlier workflow topology or previous template pins describe the state at the time and are superseded for current maintenance by Standard v2.

Verified on 2026-09-21 on `release/1.0.0-readiness` as part of the version 1.0.0 release-readiness review.

## Supported runtime baseline

Version 1 is intentionally limited to:

- PHP `^8.3`
- Laravel 13
- Filament `>=5.8.3 <6.0.0`

Filament 4 and Filament 6 are outside the supported 1.x range.

Filament 5.8.3 is the current minimum. It remains above the releases covered by the earlier MFA security baseline and includes Filament's upstream fix for the Livewire `DataStore` binding bug present in 5.8.1 and 5.8.2.

## Baseline adjustment after release-readiness

The original 1.0.0 release-readiness matrix below was executed with Filament 5.7.6 as the minimum and is preserved as historical evidence.

A later `1.x` compatibility run on 2026-09-21 (GitHub Actions run `35650931314`) tested the then-declared 5.8.1 floor and failed every lowest-dependency job on PHP 8.3, 8.4 and 8.5 while all latest-dependency jobs passed. The render failure was `ViewErrorBag::put()` receiving a null message bag from Livewire validation state.

The root cause is upstream Filament issue/PR #20515 and commit `c2537be5a111889f2226c8c1aa9f172dcf0e7fe7`: Filament Support registered Livewire's `DataStore` with `bind()`, which could replace Livewire's shared store with non-shared instances depending on provider order. Filament 5.8.3 contains the `singleton()` fix; 5.8.1 and 5.8.2 do not. The package therefore raises its supported floor to Filament 5.8.3 rather than weakening the render regression test or carrying a package-local workaround for the upstream bug.

## Original release-readiness checks

The release-readiness GitHub Actions matrix validates both the lowest and latest allowed dependency sets on every supported PHP version.

| Check | Result |
| --- | --- |
| PHP matrix | Passed on PHP 8.3, 8.4 and 8.5. |
| Lowest dependencies | Passed on PHP 8.3, 8.4 and 8.5 with Filament 5.7.6. |
| Latest dependencies | Passed on PHP 8.3, 8.4 and 8.5; Filament 5.8.4 was the latest resolved version on 2026-09-21. |
| Filament major guard | Passed; CI rejects any resolved Filament version outside major 5. |
| Composer validation | Passed with `composer validate --strict`. |
| Composer security audit | Passed with no security vulnerability advisories in both lowest and latest dependency sets. |
| Laravel Pint | Passed. |
| PHPStan | Passed. |
| PHPUnit | Passed: 113 tests, 445 assertions. |
| Documentation build | Passed with Astro/Starlight and artifact upload. |
| Code-quality workflow | Passed Composer validation, dependency audit, Pint, PHPStan, tracked PHP syntax and release-archive verification. |

## Compatibility issue found by the release matrix

The first lowest-dependency run exposed a real Filament 5.7.6 compatibility issue in the package email-MFA setup action.

The implementation referenced email-MFA `PanelsIconAlias` constants that were introduced after Filament 5.7.6. The package now resolves those icon aliases by their stable alias strings and falls back to Heroicons, matching the behavior available on Filament 5.7.6 while preserving icon registration support on later Filament 5 releases.

A regression test now builds the email-MFA setup action across the supported dependency matrix.

## CI and release hardening

The repository now includes:

- Tests and code-quality workflows on `main`, `1.x`, feature branches, release branches and `v*` tags.
- Lowest/latest dependency testing across PHP 8.3, 8.4 and 8.5.
- An explicit Filament 5 major-version guard.
- `composer audit --locked` in the test and code-quality workflows.
- Full commit-SHA pinning for third-party GitHub Actions used by the documentation workflow.
- Read-only repository permissions and disabled persisted checkout credentials where checkout is used for package CI.
- Distribution-archive checks that require runtime paths and reject development-only content, including `docs-site/`.
- Dependabot coverage for Composer and GitHub Actions with a seven-day default cooldown.
- `SECURITY.md` with a maintained 1.x support policy and private vulnerability reporting.
- `CONTRIBUTING.md` with the main/1.x branch model.
- `CHANGELOG.md` prepared for v1.0.0.

## Storage migration contract

The package supports `user` and `separate` profile storage.

Storage mode is an installation-time structural decision. Laravel records package migrations as executed even when a storage-specific migration returns early for the other mode, so changing storage mode after installation requires an application-owned migration/data migration.

The README and public documentation now describe this before the first migration command.

## Distribution archive

The code-quality workflow verifies the archive produced by `git archive`.

Required runtime paths include:

- `composer.json`
- `src/`
- `config/`
- `database/`
- `resources/`

Development-only paths are rejected from the archive, including:

- `.github/`
- `tests/`
- `docs-site/`
- `docs/superpowers/`
- `docs/verification.md`
- local quality configuration
- `CONTRIBUTING.md`
- `composer.lock`

## External verification boundary

The package has not yet been tagged as v1.0.0 in this verification record.

After the release-readiness PR is merged into `1.x`, synchronize the release commit to `main`, create the `v1.0.0` tag from the verified release commit, publish the GitHub Release, then verify Packagist and trigger or wait for a fresh Plumb scan.

External package/release scores must be reported from their published state rather than inferred from repository configuration.
