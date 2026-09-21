<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests;

use Filament\FilamentServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfileServiceProvider;
use MortalKiller\FilamentPageHeader\PageHeaderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            PageHeaderServiceProvider::class,
            CompleteUserProfileServiceProvider::class,
        ];
    }

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
