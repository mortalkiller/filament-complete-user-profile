<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('demo.timezone', 'Europe/Lisbon');
        $this->migrator->add('demo.weekly_digest', true);
    }
};
