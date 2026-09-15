<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
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
        self::assertFalse($security->hasMultiFactorAuthentication());
    }

    public function test_features_are_configured_through_the_fluent_api(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->profile(fn (Profile $profile): Profile => $profile->avatar(false))
            ->sessions()
            ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens->abilities([
                'customers:read' => 'Read customers',
            ]));

        $profile = $plugin->getFeature('profile');
        self::assertInstanceOf(Profile::class, $profile);
        self::assertFalse($profile->hasAvatar());

        $sessions = $plugin->getFeature('sessions');
        self::assertInstanceOf(Sessions::class, $sessions);
        self::assertTrue($sessions->isEnabled());

        $tokens = $plugin->getFeature('api-tokens');
        self::assertInstanceOf(ApiTokens::class, $tokens);
        self::assertSame(['customers:read' => 'Read customers'], $tokens->getAbilities());
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

    public function test_mfa_shortcut_delegates_to_security_feature(): void
    {
        $plugin = CompleteUserProfilePlugin::make()->multiFactorAuthentication();

        $security = $plugin->getFeature('security');
        self::assertInstanceOf(Security::class, $security);
        self::assertTrue($security->hasMultiFactorAuthentication());
    }
}
