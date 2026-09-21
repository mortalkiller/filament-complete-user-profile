<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\FullSecurityUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class AccountSecurityAsideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The security section's content already renders Filament's own
        // multi-factor management UI (unrelated to this task), and the
        // overview section reads profile data off the user: both require a
        // real, authenticated Eloquent model. FullSecurityUser implements
        // every contract the enabled features touch so content() can be
        // built for any section without throwing.
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('auth.defaults.guard', 'web');
        config()->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users', ['driver' => 'eloquent', 'model' => FullSecurityUser::class]);

        SchemaFacade::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->boolean('has_email_authentication')->nullable();
            $table->timestamps();
        });

        SchemaFacade::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function test_the_aside_renders_beside_overview_and_profile(): void
    {
        $page = $this->makePage($this->pluginWithSecurityFeatures());

        foreach (['overview', 'profile'] as $section) {
            $page->section = $section;
            $columns = $this->gridColumns($page);

            self::assertCount(2, $columns, "The {$section} area must render an aside.");
            self::assertSame(2, $columns[0]->getColumnSpan('lg'));
            self::assertSame(1, $columns[1]->getColumnSpan('lg'));
            self::assertInstanceOf(Section::class, $columns[1]);
            self::assertSame('Account Security', $columns[1]->getHeading());
        }
    }

    public function test_other_areas_render_full_width_without_an_aside(): void
    {
        // A plain plugin (no app/email MFA) keeps this focused on the grid
        // structure: enabling those providers makes Filament's own security
        // section eagerly render management UI, which is unrelated to what
        // this test checks and needlessly drags in Blade component wiring.
        $page = $this->makePage(CompleteUserProfilePlugin::make());
        $page->section = 'security';

        $columns = $this->gridColumns($page);

        self::assertCount(1, $columns);
        self::assertSame(3, $columns[0]->getColumnSpan('lg'));
    }

    public function test_rows_follow_the_enabled_features(): void
    {
        $page = $this->makePage($this->pluginWithSecurityFeatures());
        $page->section = 'overview';

        $aside = $this->gridColumns($page)[1];
        $schema = $aside->getChildSchema();

        if ($schema === null) {
            self::fail('The aside must expose a child schema.');
        }

        self::assertSame(
            ['Authenticator app', 'Email MFA', 'Active sessions', 'Personal access tokens'],
            array_map(
                static function ($entry): string {
                    self::assertInstanceOf(TextEntry::class, $entry);

                    $label = $entry->getLabel();
                    self::assertIsString($label);

                    return $label;
                },
                $schema->getComponents(),
            ),
        );
    }

    public function test_the_aside_disappears_when_no_security_feature_is_enabled(): void
    {
        $page = $this->makePage(CompleteUserProfilePlugin::make());
        $page->section = 'overview';

        $columns = $this->gridColumns($page);

        self::assertCount(1, $columns);
        self::assertSame(3, $columns[0]->getColumnSpan('lg'));
    }

    private function pluginWithSecurityFeatures(): CompleteUserProfilePlugin
    {
        return CompleteUserProfilePlugin::make()
            ->security(static fn (Security $security): Security => $security->appAuthentication()->emailAuthentication())
            ->sessions()
            ->apiTokens();
    }

    /** @return array<int, Component> */
    private function gridColumns(CompleteUserProfile $page): array
    {
        $components = $page->content(Schema::make($page))->getComponents();

        self::assertCount(1, $components);
        self::assertInstanceOf(Grid::class, $components[0]);

        $schema = $components[0]->getChildSchema();

        if ($schema === null) {
            self::fail('The content grid must expose a child schema.');
        }

        return array_values(array_map(
            static function ($component): Component {
                self::assertInstanceOf(Component::class, $component);

                return $component;
            },
            $schema->getComponents(),
        ));
    }

    private function makePage(CompleteUserProfilePlugin $plugin): CompleteUserProfile
    {
        Route::get('/profile', static fn (): string => 'profile')
            ->name('filament.admin.auth.profile');

        $panel = Panel::make()->id('admin')->plugin($plugin);

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        $user = FullSecurityUser::query()->create(['name' => 'Pedro', 'email' => 'pedro@example.test']);
        $this->actingAs($user);

        return app(CompleteUserProfile::class);
    }
}
