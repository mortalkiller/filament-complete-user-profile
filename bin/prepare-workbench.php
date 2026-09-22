<?php

declare(strict_types=1);

$base = dirname(__DIR__).'/workbench';

foreach ([
    'bootstrap/cache',
    'database',
    'storage/app/private',
    'storage/app/public',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
] as $directory) {
    $path = $base.'/'.$directory;

    if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to create workbench directory: '.$path);
    }
}

if (! file_exists($base.'/.env')) {
    $environment = file_get_contents($base.'/.env.example');

    if ($environment === false) {
        throw new RuntimeException('Unable to read the workbench environment template.');
    }

    $environment = str_replace(
        'APP_KEY=',
        'APP_KEY=base64:'.base64_encode(random_bytes(32)),
        $environment,
    );

    file_put_contents($base.'/.env', $environment);
}

if (! file_exists($base.'/database/database.sqlite')) {
    touch($base.'/database/database.sqlite');
}

if (! is_dir($base.'/vendor') && ! is_link($base.'/vendor')) {
    if (! symlink('../vendor', $base.'/vendor')) {
        throw new RuntimeException('Unable to link the workbench to the package vendor directory.');
    }
}

fwrite(STDOUT, "Workbench directories and local environment are ready.\n");
