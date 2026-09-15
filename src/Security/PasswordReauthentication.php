<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Security;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\Reauthentication;

class PasswordReauthentication implements Reauthentication
{
    public function isAvailable(Authenticatable $user): bool
    {
        return filled($user->getAuthPassword());
    }

    /** @return array<Component> */
    public function getFormSchema(): array
    {
        return [
            TextInput::make('current_password')
                ->label('Current password')
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->autocomplete('current-password')
                ->currentPassword(guard: Filament::getAuthGuard())
                ->required(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function confirm(Authenticatable $user, array $data): void
    {
        if (! $this->isAvailable($user)) {
            throw ValidationException::withMessages([
                'current_password' => 'This account cannot be reauthenticated with a local password.',
            ]);
        }

        Validator::make($data, [
            'current_password' => [
                'required',
                'string',
                'current_password:'.Filament::getAuthGuard(),
            ],
        ])->validate();
    }
}
