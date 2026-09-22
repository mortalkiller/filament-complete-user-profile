<?php

declare(strict_types=1);

use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;
use Workbench\App\Settings\DemoSettings;

return [
    'settings' => [
        DemoSettings::class,
    ],

    'setting_class_path' => app_path('Settings'),

    'migrations_paths' => [
        database_path('settings'),
    ],

    'default_repository' => 'database',

    'repositories' => [
        'database' => [
            'type' => DatabaseSettingsRepository::class,
            'model' => null,
            'table' => null,
            'connection' => null,
        ],
    ],

    'encoder' => null,
    'decoder' => null,

    'cache' => [
        'enabled' => false,
        'store' => null,
        'prefix' => null,
        'ttl' => null,
        'memo' => false,
    ],

    'global_casts' => [],

    'auto_discover_settings' => [],

    'discovered_settings_cache_path' => base_path('bootstrap/cache'),
];
