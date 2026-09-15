<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class MfaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->timestamps();
        });
    }

    public function test_mfa_is_off_by_default(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make());

        self::assertSame([], $panel->getMultiFactorAuthenticationProviders());
    }

    public function test_enabling_mfa_registers_filaments_recoverable_app_authentication(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make()->multiFactorAuthentication());

        $providers = $panel->getMultiFactorAuthenticationProviders();

        self::assertArrayHasKey('app', $providers);
        self::assertInstanceOf(AppAuthentication::class, $providers['app']);
        self::assertTrue($providers['app']->isRecoverable());
    }

    public function test_security_configuration_path_enables_the_same_native_provider(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make()->security(
                fn (Security $security): Security => $security->multiFactorAuthentication(),
            ));

        self::assertInstanceOf(
            AppAuthentication::class,
            $panel->getMultiFactorAuthenticationProviders()['app'] ?? null,
        );
    }

    public function test_package_exposes_the_filament_mfa_contract(): void
    {
        self::assertTrue(interface_exists(HasMultiFactorAuthentication::class));
        self::assertTrue(trait_exists('Mortalkiller\\FilamentCompleteUserProfile\\Concerns\\InteractsWithMultiFactorAuthentication'));
    }

    public function test_security_reports_missing_user_contract_clearly(): void
    {
        $security = Security::make()->multiFactorAuthentication();
        $user = new User;

        self::assertSame(
            'The authenticatable model must implement '.HasMultiFactorAuthentication::class.' when multi-factor authentication is enabled.',
            $security->getMultiFactorAuthenticationRequirementIssue($user),
        );
    }
}
