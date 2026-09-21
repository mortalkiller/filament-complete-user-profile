<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Filament\Auth\MultiFactor\Email\EmailAuthentication as FilamentEmailAuthentication;
use Filament\Panel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\Actions\SetUpEmailAuthenticationAction;
use Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\EmailAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\Notifications\VerifyEmailAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures\EmailMfaUser;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class EmailAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        config()->set('filament-complete-user-profile.user_model', EmailMfaUser::class);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->nullable();
            $table->boolean('has_email_authentication')->default(false);
            $table->timestamps();
        });

        $this->startSession();
    }

    public function test_email_authentication_registers_the_package_provider_under_filaments_native_id(): void
    {
        $panel = Panel::make()
            ->id('admin')
            ->plugin(CompleteUserProfilePlugin::make()->security(
                fn (Security $security): Security => $security->emailAuthentication(),
            ));

        $provider = $panel->getMultiFactorAuthenticationProviders()['email_code'] ?? null;

        self::assertInstanceOf(EmailAuthentication::class, $provider);
        self::assertInstanceOf(FilamentEmailAuthentication::class, $provider);
    }

    public function test_setup_action_builds_across_supported_filament_5_versions(): void
    {
        $action = SetUpEmailAuthenticationAction::make($this->makeProvider());

        self::assertSame('setUpEmailAuthentication', $action->getName());
        self::assertNotNull($action->getIcon());
        self::assertNotNull($action->getModalIcon());
    }

    public function test_first_send_starts_a_sixty_second_server_side_cooldown(): void
    {
        $provider = $this->makeProvider();
        $user = EmailMfaUser::query()->create(['email' => 'pedro@example.test']);
        Notification::fake();

        self::assertSame(60, $provider->getResendCooldownSeconds());
        self::assertTrue($provider->sendCode($user));
        self::assertFalse($provider->canSendCode($user));
        self::assertGreaterThanOrEqual(59, $provider->getResendAvailableIn($user));
        self::assertLessThanOrEqual(60, $provider->getResendAvailableIn($user));
    }

    public function test_resend_during_cooldown_is_rejected_without_queueing_another_notification(): void
    {
        $provider = $this->makeProvider();
        $user = EmailMfaUser::query()->create(['email' => 'pedro@example.test']);
        Notification::fake();

        self::assertTrue($provider->sendCode($user));
        self::assertFalse($provider->sendCode($user));

        Notification::assertCount(1);
    }

    public function test_remaining_cooldown_decreases_with_time(): void
    {
        $provider = $this->makeProvider();
        $user = EmailMfaUser::query()->create(['email' => 'pedro@example.test']);
        Notification::fake();

        self::assertTrue($provider->sendCode($user));

        $this->travel(25)->seconds();

        self::assertGreaterThanOrEqual(34, $provider->getResendAvailableIn($user));
        self::assertLessThanOrEqual(35, $provider->getResendAvailableIn($user));
    }

    public function test_resend_after_cooldown_succeeds_and_restarts_it(): void
    {
        $provider = $this->makeProvider();
        $user = EmailMfaUser::query()->create(['email' => 'pedro@example.test']);
        Notification::fake();

        self::assertTrue($provider->sendCode($user));

        $this->travel(61)->seconds();

        self::assertTrue($provider->canSendCode($user));
        self::assertSame(0, $provider->getResendAvailableIn($user));
        self::assertTrue($provider->sendCode($user));
        self::assertFalse($provider->canSendCode($user));

        Notification::assertCount(2);
    }

    public function test_resend_action_is_disabled_and_counts_down_until_available(): void
    {
        app()->setLocale('en');

        $provider = $this->makeProvider();
        $user = EmailMfaUser::query()->create(['email' => 'pedro@example.test']);
        Notification::fake();

        self::assertTrue($provider->sendCode($user));

        $action = $provider->makeResendAction($user);
        $cooldownLabel = $action->getLabel();

        self::assertIsString($cooldownLabel);
        self::assertTrue($action->isDisabled());
        self::assertStringStartsWith('Send a new code by email (', $cooldownLabel);
        self::assertStringEndsWith('s)', $cooldownLabel);
        self::assertSame('$refresh', $action->getExtraAttributes()['wire:poll.1s'] ?? null);

        $this->travel(61)->seconds();

        $availableLabel = $action->getLabel();

        self::assertIsString($availableLabel);
        self::assertFalse($action->isDisabled());
        self::assertSame('Send a new code by email', $availableLabel);
        self::assertArrayNotHasKey('wire:poll.1s', $action->getExtraAttributes());
    }

    public function test_setup_and_challenge_flows_share_the_package_resend_action(): void
    {
        $root = dirname(__DIR__, 2);
        $provider = file_get_contents($root.'/src/Security/EmailAuthentication/EmailAuthentication.php');
        $setupAction = file_get_contents($root.'/src/Security/EmailAuthentication/Actions/SetUpEmailAuthenticationAction.php');

        self::assertIsString($provider);
        self::assertIsString($setupAction);
        self::assertStringContainsString('makeResendAction($user)', $provider);
        self::assertStringContainsString('makeResendAction($user)', $setupAction);
    }

    public function test_provider_uses_a_package_owned_queued_verification_email(): void
    {
        $notification = new VerifyEmailAuthentication('483921', 4);

        self::assertSame(VerifyEmailAuthentication::class, $this->makeProvider()->getCodeNotification());
        self::assertInstanceOf(ShouldQueue::class, $notification);
        self::assertSame(['mail'], $notification->via(new EmailMfaUser));
    }

    public function test_verification_email_uses_app_branding_and_a_dedicated_otp_view(): void
    {
        config()->set('app.name', 'Acme Portal');
        app()->setLocale('en');

        $mail = (new VerifyEmailAuthentication('483921', 4))->toMail(new EmailMfaUser);

        self::assertInstanceOf(MailMessage::class, $mail);
        self::assertSame('Your Acme Portal verification code', $mail->subject);
        self::assertSame('filament-complete-user-profile::emails.verify-email-authentication', $mail->markdown);
        self::assertSame('Acme Portal', $mail->viewData['appName'] ?? null);
        self::assertSame('483 921', $mail->viewData['formattedCode'] ?? null);
        self::assertSame(4, $mail->viewData['codeExpiryMinutes'] ?? null);
    }

    public function test_verification_email_view_is_security_focused_and_has_no_call_to_action_button(): void
    {
        $root = dirname(__DIR__, 2);
        $path = $root.'/resources/views/emails/verify-email-authentication.blade.php';

        self::assertFileExists($path);

        $view = file_get_contents($path);

        self::assertIsString($view);
        self::assertStringContainsString('formattedCode', $view);
        self::assertStringContainsString('email.warning', $view);
        self::assertStringContainsString('email.ignore', $view);
        self::assertStringNotContainsString('<x-mail::button', $view);
    }

    private function makeProvider(): EmailAuthentication
    {
        return app(EmailAuthentication::class);
    }
}
