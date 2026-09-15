<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;

return new class extends Migration
{
    public function up(): void
    {
        if (config('filament-complete-user-profile.storage', 'user') !== 'separate') {
            return;
        }

        $tableName = (string) config('filament-complete-user-profile.profile_table', 'filament_user_profiles');

        if (Schema::hasTable($tableName)) {
            return;
        }

        $columns = app(ProfileColumnMap::class);

        Schema::create($tableName, function (Blueprint $table) use ($columns): void {
            $table->id();
            $table->string('user_type');
            $table->string('user_id');
            $table->string($columns->get('avatar'))->nullable();
            $table->string($columns->get('locale'), 16)->nullable();
            $table->text($columns->get('mfa_secret'))->nullable();
            $table->text($columns->get('mfa_recovery_codes'))->nullable();
            $table->timestamps();
            $table->unique(['user_type', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('filament-complete-user-profile.profile_table', 'filament_user_profiles'));
    }
};
