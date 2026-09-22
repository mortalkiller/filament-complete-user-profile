<?php

declare(strict_types=1);

namespace Workbench\App\Settings;

use Spatie\LaravelSettings\Settings;

final class DemoSettings extends Settings
{
    public string $timezone;

    public bool $weekly_digest;

    public static function group(): string
    {
        return 'demo';
    }
}
