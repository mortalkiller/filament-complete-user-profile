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
use Filament\View\PanelsIconAlias;
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
            ->label(__('filament-panels::auth/multi-factor/email/actions/set-up.label'))
            ->color('primary')
            ->icon(FilamentIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP) ?? Heroicon::LockClosed)
            ->link()
            ->mountUsing(function (Schema $schema) use ($emailAuthentication): void {
                $schema->fill();

                /** @var HasEmailAuthentication $user */
                $user = Filament::auth()->user();

                $emailAuthentication->sendCode($user);
            })
            ->modalWidth(Width::Large)
            ->modalIcon(FilamentIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP_MODAL) ?? Heroicon::OutlinedLockClosed)
            ->modalIconColor('primary')
            ->modalHeading(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.heading'))
            ->modalDescription(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.description'))
            ->schema([
                OneTimeCodeInput::make('code')
                    ->label(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.label'))
                    ->belowContent(function () use ($emailAuthentication): Action {
                        /** @var HasEmailAuthentication $user */
                        $user = Filament::auth()->user();

                        return $emailAuthentication->makeResendAction($user);
                    })
                    ->validationAttribute(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.validation_attribute'))
                    ->required()
                    ->rule(function () use ($emailAuthentication): Closure {
                        return function (string $attribute, #[SensitiveParameter] $value, Closure $fail) use ($emailAuthentication): void {
                            $rateLimitingKey = 'filament-set-up-email-authentication:'.Filament::auth()->id();

                            if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
                                $fail(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.messages.rate_limited'));

                                return;
                            }

                            RateLimiter::hit($rateLimitingKey);

                            if (is_string($value) && $emailAuthentication->verifyCode($value)) {
                                return;
                            }

                            $fail(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.form.code.messages.invalid'));
                        };
                    }),
            ])
            ->modalSubmitAction(fn (Action $action) => $action
                ->label(__('filament-panels::auth/multi-factor/email/actions/set-up.modal.actions.submit.label')))
            ->action(function (): void {
                /** @var Authenticatable&HasEmailAuthentication $user */
                $user = Filament::auth()->user();

                DB::transaction(function () use ($user): void {
                    $user->toggleEmailAuthentication(true);
                });

                Notification::make()
                    ->title(__('filament-panels::auth/multi-factor/email/actions/set-up.notifications.enabled.title'))
                    ->success()
                    ->icon(FilamentIcon::resolve(PanelsIconAlias::AUTH_MULTI_FACTOR_EMAIL_ACTIONS_SET_UP_NOTIFICATION) ?? Heroicon::OutlinedLockClosed)
                    ->send();
            })
            ->rateLimit(5);
    }
}
