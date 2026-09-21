<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\TokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;

class CheckCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-complete-user-profile.user_model', User::class);
        config()->set('filament-complete-user-profile.storage', 'user');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('locale', 16)->nullable();
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->timestamps();
        });
    }

    public function test_command_fails_when_plugin_is_not_registered_on_any_panel(): void
    {
        [$exitCode, $output] = $this->runCheck();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('Register CompleteUserProfilePlugin on at least one Filament panel.', $output);
    }

    public function test_default_enabled_features_pass_with_valid_storage(): void
    {
        $this->registerPlugin(CompleteUserProfilePlugin::make());

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('PASS', $output);
        self::assertStringContainsString('User model', $output);
        self::assertStringContainsString('Profile storage', $output);
        self::assertStringContainsString('Avatar column', $output);
        self::assertStringContainsString('Locale column', $output);
    }

    public function test_mfa_requirement_failure_is_actionable(): void
    {
        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->security(fn (Security $security): Security => $security->appAuthentication()),
        );

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('HasMultiFactorAuthentication', $output);
    }

    public function test_email_mfa_requirement_failure_is_actionable(): void
    {
        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->security(fn (Security $security): Security => $security->emailAuthentication()),
        );

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('Email MFA', $output);
        self::assertStringContainsString('HasEmailAuthentication', $output);
    }

    public function test_sessions_requirement_failure_is_actionable(): void
    {
        config()->set('session.driver', 'file');
        $this->registerPlugin(CompleteUserProfilePlugin::make()->sessions());

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('SESSION_DRIVER=database', $output);
    }

    public function test_api_token_requirement_failure_is_actionable(): void
    {
        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                    ->abilities(['customers:read' => 'Read customers'])),
        );

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('HasApiTokens', $output);
    }

    public function test_tenant_scoped_token_requirements_fail_closed_when_infrastructure_is_missing(): void
    {
        config()->set('filament-complete-user-profile.user_model', TokenUser::class);

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                    ->tenantScoped()
                    ->abilities(['customers:read' => 'Read customers'])),
        );

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('token-context migration', $output);
        self::assertStringContainsString('TokenContextResolver', $output);
    }

    public function test_the_check_reports_the_page_header_registration(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make());

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Registered by this package', $output);
    }

    public function test_the_check_reports_an_application_registered_page_header(): void
    {
        // Registering the page-header plugin before CompleteUserProfilePlugin
        // means it's already present by the time CompleteUserProfilePlugin::
        // register() runs, so the package skips its own auto-registration
        // and hasRegisteredPageHeader() must report false.
        $panel = Panel::make()
            ->id('admin')
            ->plugin(PageHeaderPlugin::make())
            ->plugin(CompleteUserProfilePlugin::make());

        app(PanelRegistry::class)->register($panel);
        Filament::setCurrentPanel($panel);

        [$exitCode, $output] = $this->runCheck();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Registered by the application', $output);
    }

    protected function registerPlugin(CompleteUserProfilePlugin $plugin): void
    {
        app(PanelRegistry::class)->register(
            Panel::make()
                ->id('admin')
                ->plugin($plugin),
        );
    }

    /** @return array{0: int, 1: string} */
    protected function runCheck(): array
    {
        $exitCode = Artisan::call('filament-complete-user-profile:check');

        return [$exitCode, Artisan::output()];
    }
}
