# Contributing

Bug reports, documentation improvements and focused pull requests are welcome.

## Reporting a problem

Use [GitHub Issues](https://github.com/mortalkiller/filament-complete-user-profile/issues) for ordinary bugs and feature requests. Include the package, PHP, Laravel and Filament versions, a minimal reproduction, reproduction steps and expected/actual behavior. Remove credentials, API tokens, recovery codes, session identifiers and real customer data from examples and screenshots.

Report vulnerabilities privately using [SECURITY.md](SECURITY.md).

## Branch model

- `main` represents the latest stable public version and is the source for production documentation.
- `1.x` is the maintained support branch for the version 1 series.
- Fixes intended for version 1 should target `1.x`, then be synchronized to `main` while 1.x remains the latest stable major.
- Breaking public API changes do not belong in the 1.x line.

## Working on a change

1. Create a focused feature or fix branch from the maintained major branch.
2. Keep reusable behavior inside the package. Consumer applications should configure behavior through the public API.
3. Prefer native Filament and Laravel APIs over custom replacements.
4. Add meaningful regression coverage for behavior changes and update the relevant documentation.
5. Run the package checks before opening a pull request and describe what passed.

Use English for code, comments and test descriptions. Keep public API changes explicit and include migration guidance for breaking changes. Avoid unrelated formatting and refactoring.

## Checks

```bash
composer validate --strict
composer check
```

`composer check` runs Pint, PHPStan and PHPUnit. CI validates PHP 8.3, 8.4 and 8.5 against both the lowest and latest dependency sets allowed by the version 1 constraints, including the supported Filament 5 range.

Do not commit dependencies, generated environment files, test reports, PHPStan logs or lockfiles produced by local library development.

## Dependency maintenance

Dependabot checks Composer and GitHub Actions weekly. Version updates use an explicit seven-day cooldown and open pull requests for review; there is no automatic merge configured by this package. Review compatibility changes and keep GitHub Actions pinned to full commit SHAs. Security updates are separate from ordinary version updates and are not delayed by the cooldown.

## Pull requests and releases

Explain the problem, resulting behavior and executed checks. Changes must be published to GitHub before external checks such as Plumb can observe them; do not claim a new external score from local configuration alone.

Maintainers publish stable versions through Git tags and GitHub Releases when the package is ready for distribution.
