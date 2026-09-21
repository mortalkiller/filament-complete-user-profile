# Development and release flow

## Work on the affected major

The current development line and default branch are `1.x`. Use permanent branches only for package majors, not for every minor or patch. A future `2.x` is created when incompatible work starts. Keep the default branch on the latest stable major until the next major's first stable release is complete.

Create a temporary `feature/*`, `fix/*`, `docs/*`, `test/*`, `refactor/*`, or `chore/*` branch from the affected major and open a PR back to that major. Review, run CI, squash the focused work and remove its temporary branch. There is no promotion merge into a separate permanent branch.

A major branch may contain unreleased work. Immutable tags identify exact published versions. Package majors are not Filament majors.

## Choose a version

A compatible fix increments PATCH (`v1.1.0` → `v1.1.1`). Compatible functionality increments MINOR (`v1.0.0` → `v1.1.0`). Incompatible public API or support changes require a MAJOR review, a new major branch and upgrade guidance (`v2.0.0`). Mark RC/beta releases as prereleases.

Fix supported older majors independently and forward-port relevant fixes with tests. Never merge an older major wholesale into a newer major.

## Publish a release

Inspect the diff from the previous tag on the same major and choose the exact commit. Complete tests, compatibility, documentation, release notes, issue acceptance and the public-content audit. Wait for successful push workflows for tests, quality and package standard on that exact commit; PR success on a different commit is not sufficient.

Create a new immutable `vX.Y.Z` tag at that verified commit on `X.x`, then publish the GitHub Release. Never move a published tag. Verify fresh PlumbPHP evidence in all four categories; distinguish stale scans from the new release ref and do not claim 100 without evidence.

## Documentation

PRs and major pushes build docs without deployment secrets. A stable release builds from the exact tag, verifies major ancestry and exact-commit CI, and publishes through `docs-production` when `DOCS_DEPLOY_ENABLED=true`.

The canonical package URL remains Latest; `/N.x/` holds the latest deployed docs for that major. Only the highest stable semantic version updates Latest. Older majors, prereleases and stale re-runs cannot downgrade newer channels. Publishing Latest preserves other major directories. The selector lists deployed versions from `versions.json`, not unreleased branches.

Manual Release documentation runs require an existing stable `release-tag`; `dry-run` defaults to true and builds without SSH/deployment. Actual manual publication must run from a major branch. Re-run after CI completes if an earlier attempt was too soon. Historical tags lacking versioned documentation must not be moved or silently built from newer branch code: publish a new release on a supported line.

After publication, check the public channel, Latest, selector, assets and links. A green build is not proof of a live deployment.

## Maintainer setup

Keep `1.x` as the default branch and protect supported majors. Configure `docs-production` to allow stable release tags and maintained major branches for manual runs. `DOCS_REMOTE_PATH` must be an absolute directory ending in this package slug, never the shared docs root. Existing SSH/rsync infrastructure is sufficient.

Before deleting an old promotion branch, preserve exclusive history and review open PR targets, branch protections, explicit `dev-main` consumers and deployment rules. Preserve published tags. Update installed agent skills deliberately; repository changes do not update local skill copies.

External workflow references and their checker/publication tooling must share a reviewed full template SHA. Adopt template improvements explicitly through PRs. See the [canonical standard](https://github.com/mortalkiller/filament-package-template/blob/1.x/docs/package-standard.md).
