<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Panel;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class CompleteUserProfilePageTest extends TestCase
{
    public function test_plugin_registers_the_native_profile_slot_with_standard_panel_layout(): void
    {
        $panel = Panel::make()->id('admin');
        $plugin = CompleteUserProfilePlugin::make();

        $plugin->register($panel);

        self::assertSame(CompleteUserProfile::class, $panel->getProfilePage());
        self::assertFalse($panel->isProfilePageSimple());
    }

    public function test_default_visible_features_match_the_account_navigation(): void
    {
        $plugin = CompleteUserProfilePlugin::make();

        self::assertSame(
            ['overview', 'profile', 'security'],
            array_keys($plugin->getVisibleFeatures()),
        );
    }

    public function test_enabled_but_hidden_features_do_not_appear_in_navigation(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->sessions(fn (Sessions $sessions): Sessions => $sessions->visible(false));

        self::assertSame(
            ['overview', 'profile', 'security'],
            array_keys($plugin->getVisibleFeatures()),
        );
    }

    public function test_enabled_visible_features_are_sorted_into_navigation(): void
    {
        $plugin = CompleteUserProfilePlugin::make()->sessions();

        self::assertSame(
            ['overview', 'profile', 'security', 'sessions'],
            array_keys($plugin->getVisibleFeatures()),
        );
    }

    public function test_package_page_view_is_registered(): void
    {
        self::assertTrue(view()->exists('filament-complete-user-profile::pages.complete-user-profile'));
    }
}
