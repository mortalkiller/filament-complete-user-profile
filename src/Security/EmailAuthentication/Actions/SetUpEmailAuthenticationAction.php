<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Facades\Filament;
use Filament\Forms\Components\OneTimeCodeInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\EmailAuthentication;
use SensitiveParameter;

class SetUpEmailAuthenticationAction
{
    public static function make(EmailAuthentication $emailAuthentication): Action
    {
        return Action::make('setUpEmailAuthentication')
            ->label(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.label'))
            ->color('primary')
            ->icon(FilamentIcon::resolve('panels::auth.multi-factor.email.actions.set-up') ?? Heroicon::LockClosed)
            ->link()
            ->mountUsing(function (Schema $schema) use ($emailAuthentication): void {
                $schema->fill();

                /** @var HasEmailAuthentication $user */
                $user = Filament::auth()->user();

                $emailAuthentication->sendCode($user);
            })
            ->modalWidth(Width::Large)
            ->modalIcon(FilamentIcon::resolve('panels::auth.multi-factor.email.actions.set-up.modal') ?? Heroicon::OutlinedLockClosed)
            ->modalIconColor('primary')
            ->modalHeading(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.heading'))
            ->modalDescription(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.description'))
            ->schema([
                OneTimeCodeInput::make('code')
                    ->label(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.label'))
                    ->belowContent(function () use ($emailAuthentication): Action {
                        /** @var HasEmailAuthentication $user */
                        $user = Filament::auth()->user();

                        return $emailAuthentication->makeResendAction($user);
                    })
                    ->validationAttribute(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.validation_attribute'))
                    ->required()
                    ->rule(function () use ($emailAuthentication): Closure {
                        return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($emailAuthentication): void {
                            $rateLimitingKey = 'filament-set-up-email-authentication:'.Filament::auth()->id();

                            if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                                $fail(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.messages.rate_limited'));

                                return;
                            }

                            RateLimiter::hit($rateLimitingKey);

                            if (is_string($value) && $emailAuthentication->verifyCode($value)) {
                                return;
                            }

                            $fail(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.messages.invalid'));
                        };
                    }),
            ])
            ->modalSubmitAction(fn (Action $action) => $action
                ->label(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.modal.actions.submit.label')))
            ->action(function (): void {
                /** @var Authenticatable&HasEmailAuthentication $user */
                $user = Filament::auth()->user();

                DB::transaction(function () use ($user): void {
                    $user->toggleEmailAuthentication(true);
                });

                Notification::make()
                    ->title(self::translate('filament-panels::auth/multi-factor/email/actions/set-up.notifications.enabled.title'))
                    ->success()
                    ->icon(FilamentIcon::resolve('panels::auth.multi-factor.email.actions.set-up.notification') ?? Heroicon::OutlinedLockClosed)
                    ->send();
            })
            ->rateLimit(5);
    }

    protected static function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }
}
