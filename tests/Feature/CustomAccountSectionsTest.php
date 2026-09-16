<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
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
}
