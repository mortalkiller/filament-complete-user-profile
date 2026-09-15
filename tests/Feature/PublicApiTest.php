<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use ReflectionClass;

class PublicApiTest extends TestCase
{
    public function test_plugin_exposes_only_the_canonical_navigation_setter(): void
    {
        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        self::assertTrue($reflection->hasMethod('navigation'));
        self::assertFalse($reflection->hasMethod('navigationLayout'));

        if (! $reflection->hasMethod('navigation')) {
            return;
        }

        $plugin = CompleteUserProfilePlugin::make();
        $navigation = $reflection->getMethod('navigation');

        self::assertSame($plugin, $navigation->invoke($plugin, AccountNavigationLayout::Sidebar));
        self::assertSame(AccountNavigationLayout::Sidebar, $plugin->getNavigationLayout());
    }

    public function test_redundant_feature_configurators_are_removed(): void
    {
        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        foreach (['overviewWith', 'profileWith', 'securityWith', 'sessionsWith', 'apiTokensWith'] as $method) {
            self::assertFalse($reflection->hasMethod($method), "[{$method}] should not be part of the public API.");
        }
    }

    public function test_security_options_are_only_configured_inside_the_security_feature(): void
    {
        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        self::assertFalse($reflection->hasMethod('multiFactorAuthentication'));
        self::assertFalse($reflection->hasMethod('emailAuthentication'));
    }

    public function test_security_uses_app_authentication_vocabulary(): void
    {
        $reflection = new ReflectionClass(Security::class);

        self::assertTrue($reflection->hasMethod('appAuthentication'));
        self::assertTrue($reflection->hasMethod('hasAppAuthentication'));
        self::assertTrue($reflection->hasMethod('getAppAuthenticationRequirementIssue'));
        self::assertFalse($reflection->hasMethod('multiFactorAuthentication'));
        self::assertFalse($reflection->hasMethod('hasMultiFactorAuthentication'));
        self::assertFalse($reflection->hasMethod('getMultiFactorAuthenticationRequirementIssue'));
    }
}
