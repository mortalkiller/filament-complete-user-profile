<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Clusters\Cluster;
use Filament\Pages\Dashboard;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\AccountPage;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class AccountPageConfigurationTest extends TestCase
{
    public function test_parameterized_page_routes_are_rejected_at_registration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('without route parameters');

        Panel::make()->id('admin')->plugin(CompleteUserProfilePlugin::make()->section(
            AccountSection::make('billing')->page(ParameterizedAccountPage::class),
        ));
    }

    public function test_cluster_members_cannot_be_account_pages(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot belong to a cluster');

        AccountSection::make('billing')->page(ClusteredAccountPage::class);
    }

    public function test_page_configuration_registered_before_the_plugin_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot use PageConfiguration');

        Panel::make()->id('admin')->pages([PageConfiguration::make(AccountPage::class, 'alternate')])
            ->plugin(CompleteUserProfilePlugin::make()->section(AccountSection::make('billing')->page(AccountPage::class)));
    }

    public function test_page_configuration_registered_after_the_plugin_is_rejected_on_boot(): void
    {
        $plugin = CompleteUserProfilePlugin::make()->section(AccountSection::make('billing')->page(AccountPage::class));
        $panel = Panel::make()->id('admin')->plugin($plugin)
            ->pages([PageConfiguration::make(AccountPage::class, 'alternate')]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot use PageConfiguration');
        $plugin->boot($panel);
    }

    public function test_page_sections_register_a_native_page_once_without_changing_its_route(): void
    {
        $section = AccountSection::make('billing')->page(AccountPage::class);
        $panel = Panel::make()->id('admin')->pages([AccountPage::class])
            ->plugin(CompleteUserProfilePlugin::make()->section($section));

        self::assertSame(AccountPage::class, $section->getPage());
        self::assertSame([], $section->getSchema());
        self::assertSame([AccountPage::class], $panel->getPages());
        self::assertSame('account/billing', AccountPage::getSlug($panel));
    }

    public function test_page_cannot_replace_an_explicit_even_empty_schema(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('either a schema or a page');

        AccountSection::make('billing')->schema([])->page(AccountPage::class);
    }

    public function test_schema_cannot_replace_a_page(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('either a schema or a page');

        AccountSection::make('billing')->page(AccountPage::class)->schema([]);
    }

    public function test_page_must_explicitly_adopt_the_account_integration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('InteractsWithAccountSection');

        AccountSection::make('billing')->page(Dashboard::class);
    }

    public function test_page_classes_cannot_back_multiple_sections_in_one_panel(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already registered');

        CompleteUserProfilePlugin::make()
            ->section(AccountSection::make('billing')->page(AccountPage::class))
            ->section(AccountSection::make('payments')->page(AccountPage::class));
    }

    public function test_a_hidden_section_still_registers_its_page_without_evaluating_access(): void
    {
        config()->set('tests.account_page_access', false);
        $panel = Panel::make()->id('admin')->plugin(
            CompleteUserProfilePlugin::make()->section(
                AccountSection::make('billing')->visible(false)->page(AccountPage::class),
            ),
        );

        self::assertSame([AccountPage::class], $panel->getPages());
    }
}

class ParameterizedAccountPage extends AccountPage
{
    protected static ?string $slug = 'billing/{record}';
}

class ClusteredAccountPage extends AccountPage
{
    protected static ?string $cluster = Cluster::class;
}
