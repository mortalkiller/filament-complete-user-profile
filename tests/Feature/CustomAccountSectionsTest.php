<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class CustomAccountSectionsTest extends TestCase
{
    public function test_sections_are_registered_through_a_fluent_singular_api(): void
    {
        $preferences = AccountSection::make('preferences');
        $notifications = AccountSection::make('notifications');
        $plugin = CompleteUserProfilePlugin::make();

        self::assertSame($plugin, $plugin->section($preferences));
        self::assertSame($plugin, $plugin->section($notifications));
        self::assertSame([
            'preferences' => $preferences,
            'notifications' => $notifications,
        ], $plugin->getSections());
    }

    public function test_invisible_sections_are_excluded_from_visible_sections(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->section(AccountSection::make('preferences'))
            ->section(AccountSection::make('notifications')->visible(false));

        self::assertSame(
            ['preferences'],
            array_keys($plugin->getVisibleSections()),
        );
    }

    public function test_visible_sections_are_sorted_by_sort_value_and_keep_registration_order_for_ties(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->section(AccountSection::make('last')->sort(30))
            ->section(AccountSection::make('first')->sort(10))
            ->section(AccountSection::make('middle-a')->sort(20))
            ->section(AccountSection::make('middle-b')->sort(20));

        self::assertSame(
            ['first', 'middle-a', 'middle-b', 'last'],
            array_keys($plugin->getVisibleSections()),
        );
    }

    public function test_builtin_feature_ids_are_reserved_for_the_package(): void
    {
        foreach (['overview', 'profile', 'security', 'sessions', 'api-tokens'] as $id) {
            $plugin = CompleteUserProfilePlugin::make();

            try {
                $plugin->section(AccountSection::make($id));
                self::fail("Built-in account section ID [{$id}] should be reserved.");
            } catch (LogicException $exception) {
                self::assertStringContainsString("[{$id}]", $exception->getMessage());
                self::assertStringContainsString('reserved', $exception->getMessage());
            }
        }
    }

    public function test_duplicate_custom_section_ids_are_rejected(): void
    {
        $plugin = CompleteUserProfilePlugin::make()
            ->section(AccountSection::make('preferences'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Account section [preferences] is already registered.');

        $plugin->section(AccountSection::make('preferences'));
    }

    public function test_navigation_merges_custom_sections_with_builtin_features_by_sort_order(): void
    {
        $this->registerProfileRoute();

        $plugin = CompleteUserProfilePlugin::make()
            ->section(
                AccountSection::make('preferences')
                    ->label('Preferences')
                    ->sort(25),
            )
            ->section(
                AccountSection::make('hidden-preferences')
                    ->sort(26)
                    ->visible(false),
            );

        $page = $this->makePage($plugin);
        $page->section = 'preferences';
        $navigation = $page->getSubNavigation();

        self::assertSame(
            ['Overview', 'Profile', 'Preferences', 'Security'],
            array_map(static fn ($item): string => $item->getLabel(), $navigation),
        );
        self::assertSame(
            [false, false, true, false],
            array_map(static fn ($item): bool => $item->isActive(), $navigation),
        );
        self::assertNull($navigation[2]->getIcon());
        self::assertStringContainsString('section=preferences', (string) $navigation[2]->getUrl());
    }

    public function test_invisible_or_invalid_selected_section_falls_back_to_first_visible_item(): void
    {
        $this->registerProfileRoute();

        $plugin = CompleteUserProfilePlugin::make()
            ->section(AccountSection::make('preferences')->visible(false));

        $page = $this->makePage($plugin);
        $page->section = 'preferences';

        self::assertSame(
            [true, false, false],
            array_map(static fn ($item): bool => $item->isActive(), $page->getSubNavigation()),
        );

        $page->section = 'missing-section';

        self::assertEquals(
            [true, false, false],
            array_map(static fn ($item): bool => $item->isActive(), $page->getSubNavigation()),
        );
    }

    public function test_content_renders_the_custom_section_schema(): void
    {
        $this->registerProfileRoute();

        $customContent = SchemaSection::make('Custom content');
        $plugin = CompleteUserProfilePlugin::make()
            ->overview(false)
            ->profile(false)
            ->security(false)
            ->section(
                AccountSection::make('preferences')
                    ->label('Preferences')
                    ->description('Manage your personal preferences.')
                    ->schema([$customContent]),
            );

        $page = $this->makePage($plugin);
        $page->section = 'preferences';
        $components = $page->content(Schema::make($page))->getComponents();

        self::assertCount(1, $components);
        self::assertInstanceOf(Grid::class, $components[0]);

        $gridSchema = $components[0]->getChildSchema();

        if ($gridSchema === null) {
            self::fail('The content grid must expose a child schema.');
        }

        $main = array_values($gridSchema->getComponents())[0];

        self::assertInstanceOf(SchemaSection::class, $main);
        self::assertSame('Preferences', $main->getHeading());

        $sectionSchema = $main->getChildSchema();

        if ($sectionSchema === null) {
            self::fail('Account section must expose a child schema.');
        }

        self::assertSame([$customContent], $sectionSchema->getComponents());
    }

    private function makePage(CompleteUserProfilePlugin $plugin): CompleteUserProfile
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

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
