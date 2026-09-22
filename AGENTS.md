# Agent instructions

This repository follows MortalKiller Filament Package Standard v2.

Canonical standard: https://github.com/mortalkiller/filament-package-standard/blob/2.x/docs/package-standard.md
Canonical skill: https://github.com/mortalkiller/filament-package-standard/blob/2.x/resources/boost/skills/developing-filament-packages/SKILL.md

Read `docs/development-flow.md`. Create temporary work branches from `1.x` and target that package major in pull requests. Releases are immutable tags on verified major commits, without a separate promotion branch. A Standard/tooling major does not by itself require a package major bump.

When available, use the `developing-filament-packages` skill before package feature, bugfix, documentation, roadmap, migration, or release work.

Before claiming completion, run the repository's required CI-equivalent checks and verify public documentation contains no private consumer, customer, infrastructure or credential information. Distinguish local/build checks from actual release and live deployment verification.

After `composer install`, read and use `vendor/mortalkiller/filament-package-standard/resources/boost/skills/developing-filament-packages/SKILL.md`. If dependencies are not installed, use the canonical skill URL above.
