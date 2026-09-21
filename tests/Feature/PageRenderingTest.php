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
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Livewire\Livewire;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

/**
 * This page's entire purpose is visual: the account navigation is meant to
 * appear exactly once, inside the page header, because
 * HasPageHeader::getCachedSubNavigation() suppresses Filament's own
 * sub-navigation slot only while $this->isRenderingPageHeader is true (set
 * by the renderingHasPageHeader() Livewire lifecycle hook). Every other test
 * in this suite asserts against schema component objects and would not
 * notice if that hook never fired and the navigation rendered twice. This
 * is the only test that actually renders the page.
 */
class PageRenderingTest extends TestCase
{
    /**
     * The shared TestCase only registers the four providers every other
     * schema-level test needs. Actually rendering the page's Blade view
     * pulls in `<x-filament-actions::modals />` (and friends) from
     * Filament's satellite packages, whose service providers a real
     * consuming application gets for free via Composer package discovery.
     * The minimal testbench app here has discovery disabled, so they must
     * be added explicitly for this one render-level test.
     *
     * @param  Application  $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);

        SchemaFacade::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Route::get('/profile', static fn (): string => 'profile')
            ->name('filament.admin.auth.profile');

        $panel = Panel::make()->id('admin')->plugin(CompleteUserProfilePlugin::make());

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        $this->actingAs(User::query()->create(['name' => 'Pedro', 'email' => 'pedro@example.test']));
    }

    public function test_the_page_renders_the_header_and_a_single_sub_navigation(): void
    {
        $component = Livewire::test(CompleteUserProfile::class);

        $component->assertSuccessful();
        $component->assertSeeHtml('data-fph-root');
        $component->assertSee('Overview');
        $component->assertSee('Pedro');

        $html = $component->html();

        // The header component itself renders the sub-navigation as a
        // responsive pair: one mobile dropdown and one desktop tab bar,
        // both inside a single `data-fph-sub-navigation` wrapper. If
        // renderingHasPageHeader() never fired and getCachedSubNavigation()
        // fell through to Filament's default (non-empty) implementation,
        // Filament's own page layout would render a *second*,
        // structurally identical nav below the header — doubling every one
        // of these counts.
        self::assertSame(
            1,
            substr_count($html, 'data-fph-sub-navigation'),
            'The header must render exactly one sub-navigation wrapper.',
        );
        self::assertSame(
            1,
            substr_count($html, 'fi-page-sub-navigation-tabs'),
            'The desktop sub-navigation tab bar must render exactly once (not once inside the header and once from a native fallback).',
        );
        self::assertSame(
            1,
            substr_count($html, 'fi-page-sub-navigation-dropdown'),
            'The mobile sub-navigation dropdown must render exactly once (not once inside the header and once from a native fallback).',
        );
    }
}
