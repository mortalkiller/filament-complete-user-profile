<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Panel;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;

class PageHeaderRegistrationTest extends TestCase
{
    public function test_page_header_plugin_is_registered_for_the_application(): void
    {
        $plugin = CompleteUserProfilePlugin::make();
        $panel = Panel::make()->id('admin')->plugin($plugin);

        self::assertTrue($panel->hasPlugin(PageHeaderPlugin::ID));
        self::assertSame('normal', $this->headerMode($panel));
        self::assertTrue($plugin->hasRegisteredPageHeader());
    }

    public function test_an_application_plugin_registered_first_is_left_alone(): void
    {
        $plugin = CompleteUserProfilePlugin::make();
        $panel = Panel::make()
            ->id('admin')
            ->plugin(PageHeaderPlugin::make()->sticky())
            ->plugin($plugin);

        self::assertSame('sticky', $this->headerMode($panel));
        self::assertFalse($plugin->hasRegisteredPageHeader());
    }

    public function test_an_application_plugin_registered_last_takes_over(): void
    {
        $plugin = CompleteUserProfilePlugin::make();
        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin)
            ->plugin(PageHeaderPlugin::make()->compact());

        self::assertSame('compact', $this->headerMode($panel));
        self::assertFalse($plugin->hasRegisteredPageHeader());
    }

    public function test_header_options_can_be_configured_through_this_plugin(): void
    {
        $panel = Panel::make()->id('admin')->plugin(
            CompleteUserProfilePlugin::make()->pageHeader(
                static fn (PageHeaderPlugin $header): PageHeaderPlugin => $header->sticky(),
            ),
        );

        self::assertSame('sticky', $this->headerMode($panel));
    }

    public function test_a_configured_instance_is_accepted_directly(): void
    {
        $panel = Panel::make()->id('admin')->plugin(
            CompleteUserProfilePlugin::make()->pageHeader(PageHeaderPlugin::make()->compact()),
        );

        self::assertSame('compact', $this->headerMode($panel));
    }

    public function test_configuration_is_ignored_when_the_application_registers_its_own(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(PageHeaderPlugin::make())
            ->plugin(
                CompleteUserProfilePlugin::make()->pageHeader(
                    static fn (PageHeaderPlugin $header): PageHeaderPlugin => $header->sticky(),
                ),
            );

        self::assertSame('normal', $this->headerMode($panel));
    }

    private function headerMode(Panel $panel): string
    {
        $plugin = $panel->getPlugin(PageHeaderPlugin::ID);

        self::assertInstanceOf(PageHeaderPlugin::class, $plugin);

        return $plugin->getOptions()->toArray()['mode'];
    }
}
