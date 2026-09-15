<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests;

use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfileServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CompleteUserProfileServiceProvider::class,
        ];
    }

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
