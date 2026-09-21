<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Storage;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Enums\BreadcrumbPosition;
use ReflectionMethod;

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

    public function test_the_navigation_layout_api_is_gone(): void
    {
        $reflection = new \ReflectionClass(CompleteUserProfilePlugin::class);

        self::assertFalse($reflection->hasMethod('navigation'));
        self::assertFalse($reflection->hasMethod('getNavigationLayout'));
        self::assertFalse(enum_exists('Mortalkiller\\FilamentCompleteUserProfile\\Enums\\AccountNavigationLayout'));
    }

    public function test_sub_navigation_lists_every_visible_account_item_without_icons(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(CompleteUserProfilePlugin::make());
        $page->section = 'security';
        $navigation = $page->getSubNavigation();

        self::assertSame(
            ['Overview', 'Profile', 'Security'],
            array_map(static fn ($item): string => $item->getLabel(), $navigation),
        );
        self::assertSame(
            [false, false, true],
            array_map(static fn ($item): bool => $item->isActive(), $navigation),
        );

        foreach ($navigation as $item) {
            self::assertNull($item->getIcon());
        }
    }

    public function test_content_renders_only_the_active_account_item(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(CompleteUserProfilePlugin::make());
        $page->section = 'security';
        $components = $page->content(Schema::make($page))->getComponents();

        self::assertCount(1, $components);
        self::assertNotInstanceOf(Tabs::class, $components[0]);
    }

    public function test_navigation_urls_always_target_the_profile_route(): void
    {
        $this->registerProfileRoute();

        $plugin = CompleteUserProfilePlugin::make();

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

    public function test_the_page_uses_the_page_header_trait_with_sub_navigation(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(CompleteUserProfilePlugin::make());

        self::assertTrue($page->pageHeaderIsEnabled());

        $header = $page->getPageHeaderComponent();

        self::assertInstanceOf(Header::class, $header);
        self::assertTrue($header->hasSubNavigation());
        self::assertSame(
            BreadcrumbPosition::Inside,
            $header->getBreadcrumbPosition(),
        );
    }

    public function test_the_heading_and_subheading_follow_the_active_account_item(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(CompleteUserProfilePlugin::make());

        $page->section = 'security';

        self::assertSame('Security', $page->getHeading());
        self::assertSame('Manage your password and account security.', $page->getSubheading());

        $page->section = 'overview';

        self::assertSame('Overview', $page->getHeading());
        self::assertSame(
            'A summary of your account and enabled security features.',
            $page->getSubheading(),
        );
    }

    public function test_a_custom_section_without_a_description_yields_a_null_subheading(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(
            CompleteUserProfilePlugin::make()->section(
                AccountSection::make('preferences')->label('Preferences'),
            ),
        );
        $page->section = 'preferences';

        self::assertSame('Preferences', $page->getHeading());
        self::assertNull($page->getSubheading());
    }

    public function test_breadcrumbs_trail_from_the_account_to_the_active_item(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(CompleteUserProfilePlugin::make());
        $page->section = 'profile';

        $breadcrumbs = $page->getBreadcrumbs();

        self::assertSame(['My account', 'Profile'], array_values($breadcrumbs));
        self::assertStringContainsString('/profile', (string) array_key_first($breadcrumbs));
    }

    public function test_content_cards_no_longer_repeat_the_header_description(): void
    {
        $this->registerProfileRoute();

        $page = $this->makePage(CompleteUserProfilePlugin::make());
        $page->section = 'security';
        $components = $page->content(Schema::make($page))->getComponents();

        self::assertInstanceOf(Grid::class, $components[0]);

        $schema = $components[0]->getChildSchema();

        if ($schema === null) {
            self::fail('The content grid must expose a child schema.');
        }

        $main = array_values($schema->getComponents())[0];

        self::assertInstanceOf(Section::class, $main);
        self::assertNull($main->getDescription());
    }

    public function test_the_avatar_resolves_a_stored_avatar_through_the_configured_disk(): void
    {
        $this->registerProfileRoute();

        $user = $this->makeAuthenticatedUser();

        $disk = Storage::fake('public');
        $disk->put('avatars/pedro.png', 'fake-avatar-contents');
        app(ProfileStorage::class)->put($user, 'avatar', 'avatars/pedro.png');

        $page = $this->makePage(CompleteUserProfilePlugin::make());

        self::assertSame(
            $disk->url('avatars/pedro.png'),
            $this->callGetAccountAvatarUrl($page),
        );
    }

    public function test_the_avatar_falls_back_to_filaments_generated_avatar_without_a_stored_avatar(): void
    {
        $this->registerProfileRoute();

        $user = $this->makeAuthenticatedUser();

        $page = $this->makePage(CompleteUserProfilePlugin::make());

        self::assertSame(
            filament()->getUserAvatarUrl($user),
            $this->callGetAccountAvatarUrl($page),
        );
    }

    private function makeAuthenticatedUser(): User
    {
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web.provider', 'users');
        config()->set('auth.providers.users.model', User::class);
        config()->set('filament-complete-user-profile.user_model', User::class);
        config()->set('filament-complete-user-profile.storage', 'separate');

        SchemaFacade::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        $migration = require __DIR__.'/../../database/migrations/0001_01_01_000002_create_filament_user_profiles_table.php';
        $migration->up();

        $user = User::query()->create(['name' => 'Pedro', 'email' => 'pedro@example.test']);

        $this->actingAs($user);

        return $user;
    }

    private function callGetAccountAvatarUrl(CompleteUserProfile $page): ?string
    {
        return (new ReflectionMethod($page, 'getAccountAvatarUrl'))->invoke($page);
    }

    private function makePage(CompleteUserProfilePlugin $plugin): CompleteUserProfile
    {
        $panel = Panel::make()->id('admin')->plugin($plugin);

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        return app(CompleteUserProfile::class);
    }

    private function registerProfileRoute(): void
    {
        Route::get('/profile', static fn (): string => 'profile')
            ->name('filament.admin.auth.profile');
    }
}
