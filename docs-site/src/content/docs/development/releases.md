---
title: Development and releases
description: Major branches, semantic versions and release-tag documentation.
---

## Development

Use permanent package-major branches (`1.x`, `2.x`, and so on), without a separate stable-promotion branch. Start a temporary feature/fix/docs branch from the affected major, open a PR back to that major, and merge after review and passing CI. Delete the temporary branch, not the supported major branch.

A branch may contain unreleased work. A tag identifies exactly what was published. Package majors are not Filament majors.

## Version numbers

Use immutable `vMAJOR.MINOR.PATCH` tags. A compatible bug fix increments PATCH; compatible functionality increments MINOR; incompatible public API or support changes require a MAJOR review and upgrade guidance.

For example, a compatible feature developed on `2.x` can become `v2.4.0`, followed by a fix in `v2.4.1`. Develop incompatible changes on `3.x`, while retaining the current stable major as the default branch until `v3.0.0` is complete. Fix supported older majors independently and forward-port relevant fixes with tests.

## Releasing

Choose the exact commit in `X.x`, inspect its diff from the previous tag on that major, and complete tests, compatibility checks, documentation, release notes and the public-content audit. Required push CI must pass on that exact commit. Verify current PlumbPHP evidence rather than assuming a previous scan covers new changes.

Create a new `vX.Y.Z` tag on the verified commit and publish the GitHub Release. No additional merge into another permanent branch is needed. Never move a published tag. Mark RC/beta releases as prereleases.

## Documentation publication

PRs and pushes only validate and build docs. A stable release publishes documentation from its exact tag after checking major ancestry and required CI. `Latest` retains the canonical package documentation URL; `/N.x/` contains the newest published docs for that major.

An older-major release cannot replace a newer Latest version. Prereleases do not replace stable docs. Re-runs cannot downgrade a channel, and publishing Latest preserves other major directories. The selector lists deployed versions, not unreleased branches.

The **Release documentation** workflow supports an existing stable `release-tag` for re-runs. `dry-run` defaults to true and builds without deployment. Actual publication requires `DOCS_DEPLOY_ENABLED=true` and the configured `docs-production` environment. Re-run after CI completes when an initial attempt was too early.

Tags predating the versioned-docs configuration must not be moved or silently rebuilt from newer branch source. Publish a new release containing the configuration on a supported line instead. Verify the public channel, Latest URL, selector, assets and links after publication; a green build alone does not prove a live deployment succeeded.
