<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\MultiFactor\Email\Actions\DisableEmailAuthenticationAction;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication as FilamentEmailAuthentication;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\Actions\SetUpEmailAuthenticationAction;
use SensitiveParameter;

class EmailAuthentication extends FilamentEmailAuthentication
{
    protected int $resendCooldownSeconds = 60;

    public function getResendCooldownSeconds(): int
    {
        return $this->resendCooldownSeconds;
    }

    public function getResendAvailableIn(HasEmailAuthentication $user): int
    {
        return RateLimiter::availableIn($this->getResendRateLimitKey($user));
    }

    public function canSendCode(HasEmailAuthentication $user): bool
    {
        return $this->getResendAvailableIn($user) === 0;
    }

    public function sendCode(HasEmailAuthentication $user): bool
    {
        $model = $this->getEmailAuthenticationModel($user);
        $rateLimitingKey = $this->getResendRateLimitKey($model);

        if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 1)) {
            return false;
        }

        RateLimiter::hit($rateLimitingKey, $this->getResendCooldownSeconds());

        $code = $this->generateCode();
        $codeExpiryMinutes = $this->getCodeExpiryMinutes();

        session()->put($this->getCodeSessionKey($model), Hash::make($code));
        session()->put($this->getCodeExpirySessionKey($model), now()->addMinutes($codeExpiryMinutes));

        $notify = [$model, 'notify'];

        if (! is_callable($notify)) {
            $modelClass = $model::class;

            throw new LogicException("Model [{$modelClass}] does not have a [notify()] method.");
        }

        $notify(app($this->getCodeNotification(), [
            'code' => $code,
            'codeExpiryMinutes' => $codeExpiryMinutes,
        ]));

        return true;
    }

    public function makeResendAction(HasEmailAuthentication $user): Action
    {
        return Action::make('resend')
            ->label(function () use ($user): string {
                $label = $this->translate('filament-complete-user-profile::profile.security.email_authentication.resend.label');
                $remaining = $this->getResendAvailableIn($user);

                return $remaining > 0 ? "{$label} ({$remaining}s)" : $label;
            })
            ->link()
            ->disabled(fn (): bool => ! $this->canSendCode($user))
            ->extraAttributes(fn (): array => $this->canSendCode($user)
                ? []
                : ['wire:poll.1s' => '$refresh'])
            ->action(function () use ($user): void {
                if (! $this->sendCode($user)) {
                    return;
                }

                Notification::make()
                    ->title($this->translate('filament-complete-user-profile::profile.security.email_authentication.resend.sent'))
                    ->success()
                    ->send();
            });
    }

    /** @return array<Action> */
    public function getActions(): array
    {
        /** @var Authenticatable&HasEmailAuthentication $user */
        $user = filament()->auth()->user();

        return [
            SetUpEmailAuthenticationAction::make($this)
                ->hidden(fn (): bool => $this->isEnabled($user)),
            DisableEmailAuthenticationAction::make($this)
                ->visible(fn (): bool => $this->isEnabled($user)),
        ];
    }

    /**
     * @param  Authenticatable&HasEmailAuthentication  $user
     * @return array<Component|Action|ActionGroup>
     */
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        return [
            OneTimeCodeInput::make('code')
                ->label($this->translate('filament-panels::auth/multi-factor/email/provider.login_form.code.label'))
                ->validationAttribute('code')
                ->belowContent($this->makeResendAction($user))
                ->required()
                ->rule(function () use ($user): Closure {
                    return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($user): void {
                        if (is_string($value) && $this->verifyCode($value, $user)) {
                            return;
                        }

                        $fail($this->translate('filament-panels::auth/multi-factor/email/provider.login_form.code.messages.invalid'));
                    };
                }),
        ];
    }

    protected function getResendRateLimitKey(HasEmailAuthentication $user): string
    {
        $model = $this->getEmailAuthenticationModel($user);

        return "filament-complete-user-profile:email-authentication:send:{$model->getKey()}";
    }

    /** @return Model&HasEmailAuthentication */
    protected function getEmailAuthenticationModel(HasEmailAuthentication $user): Model
    {
        if (! $user instanceof Model) {
            throw new LogicException('The ['.$user::class.'] class must be an instance of ['.Model::class.'] to use email authentication.');
        }

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
        }

        return $user;
    }

    protected function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }
}
