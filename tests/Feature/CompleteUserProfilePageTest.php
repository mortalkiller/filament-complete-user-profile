<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Enums\AccountNavigationLayout;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use ReflectionClass;

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

        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        if ($reflection->hasMethod('navigationLayout') === false) {
            self::fail('CompleteUserProfilePlugin::navigationLayout() is missing.');
        }

        if ($reflection->hasMethod('getNavigationLayout') === false) {
            self::fail('CompleteUserProfilePlugin::getNavigationLayout() is missing.');
        }

        $plugin = CompleteUserProfilePlugin::make();
        $tabs = constant(AccountNavigationLayout::class.'::Tabs');
        $sidebar = constant(AccountNavigationLayout::class.'::Sidebar');
        $setter = $reflection->getMethod('navigationLayout');
        $getter = $reflection->getMethod('getNavigationLayout');

        self::assertSame($tabs, $getter->invoke($plugin));
        self::assertSame($plugin, $setter->invoke($plugin, $sidebar));
        self::assertSame($sidebar, $getter->invoke($plugin));
    }

    public function test_sidebar_layout_uses_filament_native_sub_navigation(): void
    {
        $this->registerProfileRoute();

        if (enum_exists(AccountNavigationLayout::class) === false) {
            self::fail('AccountNavigationLayout enum is missing.');
        }

        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        if ($reflection->hasMethod('navigationLayout') === false) {
            self::fail('CompleteUserProfilePlugin::navigationLayout() is missing.');
        }

        $plugin = CompleteUserProfilePlugin::make();
        $sidebar = constant(AccountNavigationLayout::class.'::Sidebar');
        $reflection->getMethod('navigationLayout')->invoke($plugin, $sidebar);

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        $page = app(CompleteUserProfile::class);
        $page->section = 'security';
        $navigation = $page->getSubNavigation();
        $labels = [];
        $activeStates = [];
        $urls = [];

        foreach ($navigation as $item) {
            $labels[] = $item->getLabel();
            $activeStates[] = $item->isActive();
            $urls[] = $item->getUrl();
        }

        self::assertSame(SubNavigationPosition::Start, CompleteUserProfile::getSubNavigationPosition());
        self::assertSame(['Overview', 'Profile', 'Security'], $labels);
        self::assertSame([false, false, true], $activeStates);
        self::assertStringContainsString('section=profile', (string) $urls[1]);
    }

    public function test_sidebar_section_is_component_state_during_livewire_requests(): void
    {
        $this->registerProfileRoute();

        $plugin = CompleteUserProfilePlugin::make()
            ->navigationLayout(AccountNavigationLayout::Sidebar);

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        $page = app(CompleteUserProfile::class);
        $reflection = new ReflectionClass($page);

        if (! $reflection->hasProperty('section')) {
            self::fail('The selected sidebar section must be persisted as Livewire component state.');
        }

        $reflection->getProperty('section')->setValue($page, 'security');
        app()->instance('request', Request::create('/livewire-f64cae0d/update', 'POST'));

        $activeStates = array_map(
            static fn ($item): bool => $item->isActive(),
            $page->getSubNavigation(),
        );

        self::assertSame([false, false, true], $activeStates);
    }

    public function test_sidebar_navigation_urls_always_target_the_profile_route(): void
    {
        $this->registerProfileRoute();

        $plugin = CompleteUserProfilePlugin::make()
            ->navigationLayout(AccountNavigationLayout::Sidebar);

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        app()->instance('request', Request::create('/livewire-f64cae0d/update?section=security', 'POST'));

        $page = app(CompleteUserProfile::class);
        $urls = array_map(
            static fn ($item): ?string => $item->getUrl(),
            $page->getSubNavigation(),
        );

        self::assertStringContainsString('/profile?section=overview', (string) $urls[0]);
        self::assertStringContainsString('/profile?section=profile', (string) $urls[1]);
        self::assertStringContainsString('/profile?section=security', (string) $urls[2]);

        foreach ($urls as $url) {
            self::assertStringNotContainsString('/livewire-', (string) $url);
        }
    }

    private function registerProfileRoute(): void
    {
        Route::get('/profile', static fn (): string => 'profile')
            ->name('filament.admin.auth.profile');
    }
}
