<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use SensitiveParameter;

class VerifyEmailAuthentication extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        #[SensitiveParameter]
        public string $code,
        public int $codeExpiryMinutes,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'Laravel');

        return (new MailMessage)
            ->subject($this->translate(
                'filament-complete-user-profile::profile.security.email_authentication.email.subject',
                ['app' => $appName],
            ))
            ->markdown('filament-complete-user-profile::emails.verify-email-authentication', [
                'appName' => $appName,
                'formattedCode' => $this->formatCode($this->code),
                'codeExpiryMinutes' => $this->codeExpiryMinutes,
            ]);
    }

    protected function formatCode(string $code): string
    {
        if (strlen($code) !== 6) {
            return $code;
        }

        return substr($code, 0, 3).' '.substr($code, 3);
    }

    /** @param array<string, string> $replace */
    protected function translate(string $key, array $replace = []): string
    {
        $translation = __($key, $replace);

        return is_string($translation) ? $translation : $key;
    }
}
