<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\TokenUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

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
        $this->artisan('filament-complete-user-profile:check')
            ->expectsOutputToContain('FAIL')
            ->expectsOutputToContain('Register CompleteUserProfilePlugin on at least one Filament panel.')
            ->assertExitCode(1);
    }

    public function test_default_enabled_features_pass_with_valid_storage(): void
    {
        $this->registerPlugin(CompleteUserProfilePlugin::make());

        $this->artisan('filament-complete-user-profile:check')
            ->expectsOutputToContain('PASS')
            ->expectsOutputToContain('User model')
            ->expectsOutputToContain('Profile storage')
            ->expectsOutputToContain('Avatar column')
            ->expectsOutputToContain('Locale column')
            ->assertExitCode(0);
    }

    public function test_mfa_requirement_failure_is_actionable(): void
    {
        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->security(fn (Security $security): Security => $security->multiFactorAuthentication()),
        );

        $this->artisan('filament-complete-user-profile:check')
            ->expectsOutputToContain('FAIL')
            ->expectsOutputToContain('HasMultiFactorAuthentication')
            ->assertExitCode(1);
    }

    public function test_sessions_requirement_failure_is_actionable(): void
    {
        config()->set('session.driver', 'file');
        $this->registerPlugin(CompleteUserProfilePlugin::make()->sessions());

        $this->artisan('filament-complete-user-profile:check')
            ->expectsOutputToContain('FAIL')
            ->expectsOutputToContain('SESSION_DRIVER=database')
            ->assertExitCode(1);
    }

    public function test_api_token_requirement_failure_is_actionable(): void
    {
        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                    ->abilities(['customers:read' => 'Read customers'])),
        );

        $this->artisan('filament-complete-user-profile:check')
            ->expectsOutputToContain('FAIL')
            ->expectsOutputToContain('HasApiTokens')
            ->assertExitCode(1);
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
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $this->registerPlugin(
            CompleteUserProfilePlugin::make()
                ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                    ->tenantScoped()
                    ->abilities(['customers:read' => 'Read customers'])),
        );

        $this->artisan('filament-complete-user-profile:check')
            ->expectsOutputToContain('FAIL')
            ->expectsOutputToContain('token-context migration')
            ->expectsOutputToContain('TokenContextResolver')
            ->assertExitCode(1);
    }

    protected function registerPlugin(CompleteUserProfilePlugin $plugin): void
    {
        app(PanelRegistry::class)->register(
            Panel::make()
                ->id('admin')
                ->plugin($plugin),
        );
    }
}
