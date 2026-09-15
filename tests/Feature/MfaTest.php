<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication as FilamentHasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\MfaUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use ReflectionMethod;

class MfaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('filament-complete-user-profile.user_model', MfaUser::class);

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

    public function test_enabling_email_authentication_registers_filaments_native_email_provider(): void
    {
        $plugin = CompleteUserProfilePlugin::make();
        $this->enableEmailAuthentication($plugin);

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        $providers = $panel->getMultiFactorAuthenticationProviders();

        self::assertArrayHasKey('email_code', $providers);
        self::assertInstanceOf(EmailAuthentication::class, $providers['email_code']);
        self::assertArrayNotHasKey('app', $providers);
    }

    public function test_app_and_email_authentication_can_be_enabled_together(): void
    {
        $plugin = CompleteUserProfilePlugin::make()->multiFactorAuthentication();
        $this->enableEmailAuthentication($plugin);

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        $providers = $panel->getMultiFactorAuthenticationProviders();

        self::assertInstanceOf(AppAuthentication::class, $providers['app'] ?? null);
        self::assertInstanceOf(EmailAuthentication::class, $providers['email_code'] ?? null);
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

    public function test_security_configuration_path_can_enable_native_email_provider(): void
    {
        $plugin = CompleteUserProfilePlugin::make()->security(function (Security $security): Security {
            $this->enableEmailAuthentication($security);

            return $security;
        });

        $panel = Panel::make()
            ->id('admin')
            ->plugin($plugin);

        self::assertInstanceOf(
            EmailAuthentication::class,
            $panel->getMultiFactorAuthenticationProviders()['email_code'] ?? null,
        );
    }

    public function test_package_exposes_the_filament_mfa_contract(): void
    {
        self::assertTrue(interface_exists(HasMultiFactorAuthentication::class));
        self::assertTrue(trait_exists('Mortalkiller\\FilamentCompleteUserProfile\\Concerns\\InteractsWithMultiFactorAuthentication'));
    }

    public function test_package_exposes_email_authentication_storage_trait(): void
    {
        self::assertTrue(
            trait_exists('Mortalkiller\\FilamentCompleteUserProfile\\Concerns\\InteractsWithEmailAuthentication'),
            'The package must provide an email-authentication storage trait compatible with its profile storage modes.',
        );
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

    public function test_security_reports_missing_email_authentication_contract_clearly(): void
    {
        $security = Security::make()->emailAuthentication();

        self::assertTrue(
            method_exists($security, 'getEmailAuthenticationRequirementIssue'),
            Security::class.' must expose getEmailAuthenticationRequirementIssue().',
        );

        $issue = (new ReflectionMethod($security, 'getEmailAuthenticationRequirementIssue'))
            ->invoke($security, new User);

        self::assertSame(
            'The authenticatable model must implement '.FilamentHasEmailAuthentication::class.' when email authentication is enabled.',
            $issue,
        );
    }

    public function test_email_authentication_readiness_requires_notifications(): void
    {
        $security = Security::make()->emailAuthentication();
        $user = new class extends User implements FilamentHasEmailAuthentication
        {
            public function hasEmailAuthentication(): bool
            {
                return false;
            }

            public function toggleEmailAuthentication(bool $condition): void {}
        };

        self::assertTrue(method_exists($security, 'getEmailAuthenticationRequirementIssue'));

        $issue = (new ReflectionMethod($security, 'getEmailAuthenticationRequirementIssue'))
            ->invoke($security, $user);

        self::assertSame(
            'The authenticated user model must support Laravel notifications to use email authentication.',
            $issue,
        );
    }

    public function test_security_page_includes_native_mfa_management_for_email_authentication(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/src/Pages/CompleteUserProfile.php');

        self::assertIsString($source);
        self::assertStringContainsString('$security->hasEmailAuthentication()', $source);
    }

    public function test_mfa_trait_encrypts_and_hides_user_storage_values(): void
    {
        $user = MfaUser::query()->create(['email' => 'pedro@example.test']);

        $user->saveAppAuthenticationSecret('totp-secret');
        $user->saveAppAuthenticationRecoveryCodes(['code-one', 'code-two']);
        $user->refresh();

        self::assertSame('totp-secret', $user->getAppAuthenticationSecret());
        self::assertSame(['code-one', 'code-two'], $user->getAppAuthenticationRecoveryCodes());
        self::assertNotSame('totp-secret', $user->getRawOriginal('app_authentication_secret'));
        self::assertStringNotContainsString(
            'code-one',
            (string) $user->getRawOriginal('app_authentication_recovery_codes'),
        );
        self::assertArrayNotHasKey('app_authentication_secret', $user->toArray());
        self::assertArrayNotHasKey('app_authentication_recovery_codes', $user->toArray());
    }

    private function enableEmailAuthentication(object $configurator): void
    {
        self::assertTrue(
            method_exists($configurator, 'emailAuthentication'),
            $configurator::class.' must expose emailAuthentication().',
        );

        (new ReflectionMethod($configurator, 'emailAuthentication'))->invoke($configurator);
    }
}
