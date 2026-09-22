<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;

$builder = Application::configure(basePath: $APP_BASE_PATH ?? dirname(__DIR__))
    ->withMiddleware()
    ->withExceptions();

if (! isset($APP_BASE_PATH)) {
    $builder->withRouting(web: __DIR__.'/../routes/web.php');
}

return $builder->create();
