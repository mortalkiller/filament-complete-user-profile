<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;
use Mortalkiller\FilamentCompleteUserProfile\Support\UserModelResolver;

return new class extends Migration
{
    public function up(): void
    {
        $storage = config('filament-complete-user-profile.storage', 'user');

        $tableName = match ($storage) {
            'user' => app(UserModelResolver::class)->table(),
            'separate' => config('filament-complete-user-profile.profile_table', 'filament_user_profiles'),
            default => null,
        };

        if (! is_string($tableName) || $tableName === '' || ! Schema::hasTable($tableName)) {
            return;
        }

        $column = app(ProfileColumnMap::class)->get('mfa_email_enabled');

        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column): void {
            $table->boolean($column)->default(false);
        });
    }

    public function down(): void
    {
        // Storage columns are intentionally preserved on rollback.
    }
};
