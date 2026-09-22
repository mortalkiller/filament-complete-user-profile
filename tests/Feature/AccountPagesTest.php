<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Enums\Width;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\AccountPage;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class AccountPagesTest extends TestCase
{
    public function test_profile_width_does_not_override_routed_page_width(): void
    {
        $this->panel(CompleteUserProfilePlugin::make()->maxContentWidth(Width::SixExtraLarge)
            ->section(AccountSection::make('billing')->page(AccountPage::class)));

        self::assertNull(app(AccountPage::class)->getMaxContentWidth());
    }

    /** @param Application $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class, SupportServiceProvider::class,
            ActionsServiceProvider::class, FormsServiceProvider::class,
            InfolistsServiceProvider::class, NotificationsServiceProvider::class,
            SchemasServiceProvider::class, TablesServiceProvider::class, WidgetsServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    private function authenticate(): void
    {
        $this->actingAs(User::query()->create(['name' => 'Alex', 'email' => 'alex@example.test']));
    }

    private function panel(?CompleteUserProfilePlugin $plugin = null, string $id = 'admin', bool $tenant = false): Panel
    {
        $plugin ??= CompleteUserProfilePlugin::make()->section(
            AccountSection::make('billing')->label('Billing')->description('Billing details')->page(AccountPage::class),
        );
        $panel = Panel::make()->id($id)->path($id)->plugin($plugin);
        if ($tenant) {
            $panel->tenant(User::class);
        }
        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);
        Route::get("/{$id}/profile", CompleteUserProfile::class)->middleware('web')->name("filament.{$id}.auth.profile");
        Route::prefix($tenant ? "/{$id}/{tenant}" : "/{$id}")->name("filament.{$id}.")->middleware('web')
            ->group(static fn () => AccountPage::registerRoutes($panel));

        return $panel;
    }

    public function test_routed_page_renders_shared_header_once_and_preserves_native_mount_and_actions(): void
    {
        $this->authenticate();
        $this->panel();
        $page = Livewire::test(AccountPage::class)
            ->assertSet('counter', 10)
            ->assertSee('Billing details')
            ->assertSee('Alex')
            ->call('mountAction', 'increment')
            ->assertSet('counter', 11)
            ->assertSee('Counter: 11');

        self::assertSame(1, substr_count($page->html(), 'data-fph-sub-navigation'));
        self::assertSame(1, substr_count($page->html(), 'fi-page-sub-navigation-tabs'));
        self::assertStringContainsString('/admin/profile?section=security', $page->html());
        self::assertFalse(AccountPage::shouldRegisterNavigation());
        /** @var AccountPage $instance */
        $instance = $page->instance();
        $navigation = $instance->getSubNavigation();
        self::assertSame([false, false, false, true], array_map(static fn ($item) => $item->isActive(), $navigation));
    }

    public function test_profile_links_to_page_route_but_keeps_inline_selection_for_page_query_ids(): void
    {
        $this->authenticate();
        $this->panel();
        Livewire::withQueryParams(['section' => 'billing']);

        $page = Livewire::test(CompleteUserProfile::class)
            ->assertSeeHtml('/admin/account/billing');

        /** @var CompleteUserProfile $instance */
        $instance = $page->instance();
        self::assertSame('Overview', $instance->getHeading());
    }

    public function test_hidden_page_is_directly_accessible_but_absent_from_navigation(): void
    {
        $this->authenticate();
        $this->panel(CompleteUserProfilePlugin::make()->section(
            AccountSection::make('billing')->page(AccountPage::class)->visible(false),
        ));
        $page = Livewire::test(AccountPage::class)->assertSee('Counter: 10');

        /** @var AccountPage $instance */
        $instance = $page->instance();
        self::assertCount(3, $instance->getSubNavigation());
        self::assertSame([false, false, false], array_map(static fn ($item) => $item->isActive(), $instance->getSubNavigation()));
    }

    public function test_denied_pages_are_hidden_and_forbidden_on_direct_requests(): void
    {
        $this->authenticate();
        $this->panel();
        config()->set('tests.account_page_access', false);

        Livewire::test(AccountPage::class)->assertForbidden();
        Livewire::test(CompleteUserProfile::class)->assertDontSeeHtml('/admin/account/billing');
    }

    public function test_revoked_page_access_blocks_an_existing_livewire_action(): void
    {
        $this->authenticate();
        $this->panel();
        $page = Livewire::test(AccountPage::class)->assertSet('counter', 10);
        config()->set('tests.account_page_access', false);

        $page->call('mountAction', 'increment')->assertForbidden();
    }

    public function test_page_cannot_run_in_an_unmapped_panel(): void
    {
        $this->authenticate();
        $this->panel();
        $this->panel(CompleteUserProfilePlugin::make(), 'other');

        Livewire::test(AccountPage::class)->assertNotFound();
    }

    public function test_page_metadata_is_resolved_from_the_current_panel(): void
    {
        $this->authenticate();
        $this->panel();
        $this->panel(CompleteUserProfilePlugin::make()->section(
            AccountSection::make('payments')->label('Payments')->page(AccountPage::class),
        ), 'other');

        $page = Livewire::test(AccountPage::class);
        /** @var AccountPage $instance */
        $instance = $page->instance();
        self::assertSame('Payments', $instance->getHeading());
        self::assertStringContainsString('/other/account/billing', $page->html());
        self::assertStringNotContainsString('/admin/account/billing', $page->html());
    }

    public function test_profile_with_only_pages_redirects_to_the_first_accessible_page(): void
    {
        $this->authenticate();
        $this->panel(CompleteUserProfilePlugin::make()->overview(false)->profile(false)->security(false)
            ->section(AccountSection::make('billing')->page(AccountPage::class)));

        Livewire::test(CompleteUserProfile::class)->assertRedirect(AccountPage::getUrl());
    }

    public function test_tenant_navigation_requires_an_explicit_current_tenant(): void
    {
        $this->authenticate();
        $this->panel(tenant: true);
        $page = app(CompleteUserProfile::class);
        self::assertCount(3, $page->getSubNavigation());

        $tenant = User::query()->create(['name' => 'Team', 'email' => 'team@example.test']);
        Filament::setTenant($tenant);
        $navigation = $page->getSubNavigation();
        self::assertCount(4, $navigation);
        self::assertStringContainsString("/admin/{$tenant->getKey()}/account/billing", (string) $navigation[3]->getUrl());
    }

    public function test_a_page_only_profile_with_no_accessible_destination_keeps_an_empty_state(): void
    {
        $this->authenticate();
        $this->panel(CompleteUserProfilePlugin::make()->overview(false)->profile(false)->security(false)
            ->section(AccountSection::make('billing')->page(AccountPage::class)));
        config()->set('tests.account_page_access', false);

        Livewire::test(CompleteUserProfile::class)->assertNoRedirect()
            ->assertDontSeeHtml('/admin/account/billing');
    }
}
