<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication as FilamentEmailAuthentication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use LogicException;

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
            throw new LogicException("Model [{$model::class}] does not have a [notify()] method.");
        }

        $notify(app($this->getCodeNotification(), [
            'code' => $code,
            'codeExpiryMinutes' => $codeExpiryMinutes,
        ]));

        return true;
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
}
