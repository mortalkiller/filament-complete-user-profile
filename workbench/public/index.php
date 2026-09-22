<?php

declare(strict_types=1);

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
