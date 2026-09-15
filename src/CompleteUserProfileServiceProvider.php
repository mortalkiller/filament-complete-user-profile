<?php

namespace Mortalkiller\FilamentCompleteUserProfile;

use Illuminate\Support\ServiceProvider;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Storage\SeparateProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Storage\UserProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;
use Mortalkiller\FilamentCompleteUserProfile\Support\UserModelResolver;

class CompleteUserProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-complete-user-profile.php',
            'filament-complete-user-profile',
        );

        $this->app->singleton(UserModelResolver::class);
        $this->app->singleton(ProfileColumnMap::class);

        $this->app->bind(ProfileStorage::class, function (): ProfileStorage {
            return match (config('filament-complete-user-profile.storage', 'user')) {
                'user' => app(UserProfileStorage::class),
                'separate' => app(SeparateProfileStorage::class),
                default => throw new LogicException('Profile storage must be either [user] or [separate].'),
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-complete-user-profile');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'filament-complete-user-profile');

        $this->publishes([
            __DIR__.'/../config/filament-complete-user-profile.php' => config_path('filament-complete-user-profile.php'),
        ], 'filament-complete-user-profile-config');
    }
}
