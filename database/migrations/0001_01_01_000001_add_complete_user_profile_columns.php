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
        if (config('filament-complete-user-profile.storage', 'user') !== 'user') {
            return;
        }

        $tableName = app(UserModelResolver::class)->table();
        $columns = app(ProfileColumnMap::class);

        $this->addColumnIfMissing($tableName, $columns->get('avatar'), fn (Blueprint $table, string $column) => $table->string($column)->nullable());
        $this->addColumnIfMissing($tableName, $columns->get('locale'), fn (Blueprint $table, string $column) => $table->string($column, 16)->nullable());
        $this->addColumnIfMissing($tableName, $columns->get('mfa_secret'), fn (Blueprint $table, string $column) => $table->text($column)->nullable());
        $this->addColumnIfMissing($tableName, $columns->get('mfa_recovery_codes'), fn (Blueprint $table, string $column) => $table->text($column)->nullable());
    }

    public function down(): void
    {
        // Application-owned columns are intentionally preserved on rollback.
    }

    /** @param Closure(Blueprint, string): mixed $definition */
    protected function addColumnIfMissing(string $tableName, string $column, Closure $definition): void
    {
        if (Schema::hasColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $definition): void {
            $definition($table, $column);
        });
    }
};
