<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Storage\SeparateProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Storage\UserProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class ProfileStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web.provider', 'users');
        config()->set('auth.providers.users.model', User::class);
        config()->set('filament-complete-user-profile.user_model', User::class);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('preferred_locale')->nullable();
            $table->timestamps();
        });
    }

    public function test_user_storage_reuses_configured_existing_columns_and_adds_only_missing_columns(): void
    {
        config()->set('filament-complete-user-profile.storage', 'user');
        config()->set('filament-complete-user-profile.columns.locale', 'preferred_locale');
        config()->set('filament-complete-user-profile.columns.avatar', 'avatar_path');

        $migration = require __DIR__.'/../../database/migrations/0001_01_01_000001_add_complete_user_profile_columns.php';
        $migration->up();
        $migration->up();

        self::assertTrue(Schema::hasColumn('users', 'preferred_locale'));
        self::assertTrue(Schema::hasColumn('users', 'avatar_path'));
        self::assertTrue(Schema::hasColumn('users', 'app_authentication_secret'));
        self::assertTrue(Schema::hasColumn('users', 'app_authentication_recovery_codes'));
        self::assertFalse(Schema::hasColumn('users', 'locale'));

        $user = User::query()->create(['name' => 'Pedro', 'email' => 'pedro@example.test']);
        $storage = app(ProfileStorage::class);

        self::assertInstanceOf(UserProfileStorage::class, $storage);

        $storage->putMany($user, [
            'locale' => 'pt',
            'avatar' => 'avatars/pedro.png',
        ]);

        $user->refresh();

        self::assertSame('pt', $user->getAttribute('preferred_locale'));
        self::assertSame('avatars/pedro.png', $user->getAttribute('avatar_path'));

        $migration->down();
        self::assertTrue(Schema::hasColumn('users', 'preferred_locale'));
        self::assertTrue(Schema::hasColumn('users', 'avatar_path'));
    }

    public function test_separate_storage_uses_a_package_owned_profile_record(): void
    {
        config()->set('filament-complete-user-profile.storage', 'separate');

        $migration = require __DIR__.'/../../database/migrations/0001_01_01_000002_create_filament_user_profiles_table.php';
        $migration->up();

        $user = User::query()->create(['name' => 'Pedro', 'email' => 'pedro@example.test']);
        $storage = app(ProfileStorage::class);

        self::assertInstanceOf(SeparateProfileStorage::class, $storage);

        $storage->putMany($user, [
            'locale' => 'pt',
            'avatar' => 'avatars/pedro.png',
        ]);

        self::assertSame('pt', $storage->get($user, 'locale'));
        self::assertSame('avatars/pedro.png', $storage->get($user, 'avatar'));
        self::assertDatabaseHas('filament_user_profiles', [
            'user_type' => User::class,
            'user_id' => (string) $user->getKey(),
            'locale' => 'pt',
        ]);

        $migration->down();
        self::assertFalse(Schema::hasTable('filament_user_profiles'));
    }
}
