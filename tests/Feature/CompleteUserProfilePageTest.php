<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelRegistry;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;
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

    public function test_account_navigation_uses_native_filament_tabs_without_custom_layout_markup(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/pages/complete-user-profile.blade.php');
        $page = file_get_contents($root.'/src/Pages/CompleteUserProfile.php');

        self::assertIsString($view);
        self::assertIsString($page);
        self::assertStringNotContainsString('fcup-account-layout', $view);
        self::assertStringNotContainsString('fcup-account-navigation', $view);
        self::assertStringNotContainsString('<aside', $view);
        self::assertStringContainsString('use Filament\\Schemas\\Components\\Tabs;', $page);
        self::assertStringContainsString('Tabs::make(', $page);
    }

    public function test_navigation_layout_defaults_to_tabs_and_can_be_switched_to_sidebar(): void
    {
        if (enum_exists(AccountNavigationLayout::class) === false) {
            self::fail('AccountNavigationLayout enum is missing.');
        }

        if (method_exists(CompleteUserProfilePlugin::class, 'navigationLayout') === false) {
            self::fail('CompleteUserProfilePlugin::navigationLayout() is missing.');
        }

        if (method_exists(CompleteUserProfilePlugin::class, 'getNavigationLayout') === false) {
            self::fail('CompleteUserProfilePlugin::getNavigationLayout() is missing.');
        }

        $plugin = CompleteUserProfilePlugin::make();

        self::assertSame(AccountNavigationLayout::Tabs, $plugin->getNavigationLayout());
        self::assertSame($plugin, $plugin->navigationLayout(AccountNavigationLayout::Sidebar));
        self::assertSame(AccountNavigationLayout::Sidebar, $plugin->getNavigationLayout());
    }

    public function test_sidebar_layout_uses_filament_native_sub_navigation(): void
    {
        if (enum_exists(AccountNavigationLayout::class) === false) {
            self::fail('AccountNavigationLayout enum is missing.');
        }

        if (method_exists(CompleteUserProfilePlugin::class, 'navigationLayout') === false) {
            self::fail('CompleteUserProfilePlugin::navigationLayout() is missing.');
        }

        $plugin = CompleteUserProfilePlugin::make()
            ->navigationLayout(AccountNavigationLayout::Sidebar);

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        request()->query->set('section', 'security');

        $page = app(CompleteUserProfile::class);
        $navigation = $page->getSubNavigation();

        self::assertSame(SubNavigationPosition::Start, CompleteUserProfile::getSubNavigationPosition());
        self::assertSame(['Overview', 'Profile', 'Security'], array_map(
            static fn (NavigationItem $item): string => $item->getLabel(),
            $navigation,
        ));
        self::assertSame([false, false, true], array_map(
            static fn (NavigationItem $item): bool => $item->isActive(),
            $navigation,
        ));
        self::assertStringContainsString('section=profile', (string) $navigation[1]->getUrl());
    }
}
