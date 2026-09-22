# Agent instructions

This repository follows MortalKiller Filament Package Standard v1.

Canonical standard: https://github.com/mortalkiller/filament-package-standard/blob/1.x/docs/package-standard.md
Canonical skill: https://github.com/mortalkiller/filament-package-standard/blob/1.x/resources/boost/skills/developing-filament-packages/SKILL.md

Read `docs/development-flow.md`. Create temporary work branches from `1.x` and target that major in pull requests. Releases are immutable tags on verified major commits, without a separate promotion branch. PR/push documentation is validation-only; stable release publication uses the exact tag.

When available, use the `developing-filament-packages` skill before package feature, bugfix, documentation, roadmap, or release work.

Before claiming completion, run the repository's required CI-equivalent checks and verify public documentation contains no private consumer, customer, or infrastructure information. Distinguish local/build checks from actual release and live deployment verification.

After `composer install`, read and use `vendor/mortalkiller/filament-package-standard/resources/boost/skills/developing-filament-packages/SKILL.md`. If dependencies are not installed, use the canonical skill URL above.
