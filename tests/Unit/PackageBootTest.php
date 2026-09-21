<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfileServiceProvider;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_service_provider_is_loaded(): void
    {
        $app = $this->app;

        self::assertNotNull($app);
        self::assertArrayHasKey(
            CompleteUserProfileServiceProvider::class,
            $app->getLoadedProviders(),
        );
    }
}
