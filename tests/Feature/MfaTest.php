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
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\EmailMfaUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\MfaUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\User;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

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

    public function test_enabling_app_authentication_registers_filaments_recoverable_provider(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make()->security(
                fn (Security $security): Security => $security->appAuthentication(),
            ));

        $providers = $panel->getMultiFactorAuthenticationProviders();

        self::assertArrayHasKey('app', $providers);
        self::assertInstanceOf(AppAuthentication::class, $providers['app']);
        self::assertTrue($providers['app']->isRecoverable());
    }

    public function test_enabling_email_authentication_registers_filaments_native_email_provider(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make()->security(
                fn (Security $security): Security => $security->emailAuthentication(),
            ));

        $providers = $panel->getMultiFactorAuthenticationProviders();

        self::assertArrayHasKey('email_code', $providers);
        self::assertInstanceOf(EmailAuthentication::class, $providers['email_code']);
        self::assertArrayNotHasKey('app', $providers);
    }

    public function test_app_and_email_authentication_can_be_enabled_together(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make()->security(
                fn (Security $security): Security => $security
                    ->appAuthentication()
                    ->emailAuthentication(),
            ));

        $providers = $panel->getMultiFactorAuthenticationProviders();

        self::assertInstanceOf(AppAuthentication::class, $providers['app'] ?? null);
        self::assertInstanceOf(EmailAuthentication::class, $providers['email_code'] ?? null);
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
        self::assertInstanceOf(FilamentHasEmailAuthentication::class, new EmailMfaUser);
    }

    public function test_security_reports_missing_user_contract_clearly(): void
    {
        $security = Security::make()->appAuthentication();
        $user = new User;

        self::assertSame(
            'The authenticatable model must implement '.HasMultiFactorAuthentication::class.' when multi-factor authentication is enabled.',
            $security->getAppAuthenticationRequirementIssue($user),
        );
    }

    public function test_security_reports_missing_email_authentication_contract_clearly(): void
    {
        $security = Security::make()->emailAuthentication();

        self::assertSame(
            'The authenticatable model must implement '.FilamentHasEmailAuthentication::class.' when email authentication is enabled.',
            $security->getEmailAuthenticationRequirementIssue(new User),
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

        self::assertSame(
            'The authenticated user model must support Laravel notifications to use email authentication.',
            $security->getEmailAuthenticationRequirementIssue($user),
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
}
