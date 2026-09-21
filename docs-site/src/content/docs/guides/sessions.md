---
title: Browser sessions
description: List and revoke Laravel database-backed browser sessions.
---

Browser-session management is disabled by default.

## Requirements

The built-in session store supports Laravel's database session driver:

```dotenv
SESSION_DRIVER=database
```

Create Laravel's sessions table if necessary:

```bash
php artisan make:session-table
php artisan migrate
```

## Enable sessions

```php
CompleteUserProfilePlugin::make()
    ->sessions();
```

## Behavior

The account center:

- lists sessions that belong to the authenticated user;
- identifies the current session;
- prevents the row action from terminating the current session;
- can revoke all other sessions after reauthentication.

Sensitive revocation flows use the package `Reauthentication` contract. The default implementation is password-based when password confirmation is available.

Applications with passwordless or custom authentication can replace that implementation through the container. See [Extension points](../../api/extension-points/).
