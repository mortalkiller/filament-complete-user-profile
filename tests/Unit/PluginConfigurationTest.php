<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Tenancy\FilamentTenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class PluginConfigurationTest extends TestCase
{
    public function test_default_features_match_the_package_contract(): void
    {
        $plugin = CompleteUserProfilePlugin::make();

        self::assertTrue($plugin->getFeature('overview')->isEnabled());
        self::assertTrue($plugin->getFeature('profile')->isEnabled());
        self::assertTrue($plugin->getFeature('security')->isEnabled());
        self::assertFalse($plugin->getFeature('sessions')->isEnabled());
        self::assertFalse($plugin->getFeature('api-tokens')->isEnabled());

        $security = $plugin->getFeature('security');
        self::assertInstanceOf(Security::class, $security);
        self::assertTrue($security->hasPassword());
        self::assertFalse($security->hasAppAuthentication());
        self::assertFalse($security->hasEmailAuthentication());
    }

    public function test_features_are_configured_through_the_fluent_api(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->profile(fn (Profile $profile): Profile => $profile->avatar(false))
            ->security(fn (Security $security): Security => $security->appAuthentication())
            ->sessions()
            ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens->abilities([
                'customers:read' => 'Read customers',
            ]));

        $profile = $plugin->getFeature('profile');
        self::assertInstanceOf(Profile::class, $profile);
        self::assertFalse($profile->hasAvatar());

        $security = $plugin->getFeature('security');
        self::assertInstanceOf(Security::class, $security);
        self::assertTrue($security->hasAppAuthentication());

        $sessions = $plugin->getFeature('sessions');
        self::assertInstanceOf(Sessions::class, $sessions);
        self::assertTrue($sessions->isEnabled());

        $tokens = $plugin->getFeature('api-tokens');
        self::assertInstanceOf(ApiTokens::class, $tokens);
        self::assertSame(['customers:read' => 'Read customers'], $tokens->getAbilities());
    }

    public function test_tenancy_resolver_accepts_class_strings_instances_and_closures(): void
    {
        $plugin = CompleteUserProfilePlugin::make();

        $plugin->tenancyResolver(FilamentTenancyResolver::class);
        self::assertSame(FilamentTenancyResolver::class, $plugin->getTenancyResolver());

        $instance = new class implements TenancyResolver
        {
            public function resolve(): ?Model
            {
                return null;
            }
        };

        $plugin->tenancyResolver($instance);
        self::assertSame($instance, $plugin->getTenancyResolver());

        $closure = static fn (): ?Model => null;

        $plugin->tenancyResolver($closure);
        self::assertSame($closure, $plugin->getTenancyResolver());
        self::assertTrue($plugin->hasCustomTenancyResolver());
    }

    public function test_tenancy_resolver_rejects_invalid_class_strings(): void
    {
        $this->expectException(LogicException::class);

        CompleteUserProfilePlugin::make()->tenancyResolver(self::class);
    }

    public function test_boolean_configuration_disables_features(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->profile(false)
            ->sessions(false)
            ->apiTokens(false);

        self::assertFalse($plugin->getFeature('profile')->isEnabled());
        self::assertFalse($plugin->getFeature('sessions')->isEnabled());
        self::assertFalse($plugin->getFeature('api-tokens')->isEnabled());
    }

    public function test_visibility_is_independent_from_enablement(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->sessions(fn (Sessions $sessions): Sessions => $sessions->visible(fn (): bool => false));

        $sessions = $plugin->getFeature('sessions');
        self::assertInstanceOf(Sessions::class, $sessions);
        self::assertTrue($sessions->isEnabled());
        self::assertFalse($sessions->isVisible());
    }
}
