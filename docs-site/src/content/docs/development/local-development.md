---
title: Local package development
description: Test unreleased package changes in a generic consuming Filament application.
---

Keep the consuming application and package as separate Git repositories.

The names and paths below are deliberately fictional examples.

## Example directory layout

```text
projects/
  demo-filament-app/
  filament-complete-user-profile/
```

## Composer path repository

Add a path repository to the consuming application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../filament-complete-user-profile",
            "options": {
                "symlink": true,
                "versions": {
                    "mortalkiller/filament-complete-user-profile": "1.x-dev"
                }
            }
        }
    ]
}
```

Then require the development version from the consuming application:

```bash
composer require mortalkiller/filament-complete-user-profile:1.x-dev
php artisan migrate
```

Composer should report that the package was symlinked. Do not edit a copied `vendor/` directory and expect changes to return to the package repository.

## Generic Docker example

Both repositories must be visible to the PHP/Composer environment at paths that keep the path repository resolvable.

```text
/var/www/app
/var/www/filament-complete-user-profile
```

A generic Compose setup might contain:

```yaml
services:
  php:
    volumes:
      - ../demo-filament-app:/var/www/app
      - ../filament-complete-user-profile:/var/www/filament-complete-user-profile
```

Commands for that fictional layout would look like:

```bash
docker compose exec -w /var/www/app php composer require mortalkiller/filament-complete-user-profile:1.x-dev
docker compose exec -w /var/www/app php php artisan migrate
```

Use the actual service names and paths from your own local environment. Never carry local path repositories into production deployments.

## Change and verify

For package-only verification:

```bash
composer validate --strict
composer check
```

In the consuming application, also run:

```bash
php artisan filament-complete-user-profile:check
```

When adding a new migration or changing Composer autoload metadata, refresh the consuming application's dependency/autoload state as appropriate.

## Return to a distributable dependency

Before testing a release-like install:

1. remove the temporary path repository;
2. require the intended stable package constraint;
3. update the package dependency;
4. verify the lock file no longer points at a local path;
5. run `composer install` in a clean checkout without the sibling package directory.

Do not commit credentials, tokens, recovery codes, real user data, `vendor/`, `node_modules/` or generated local environment files.
