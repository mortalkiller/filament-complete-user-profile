<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Support;

use InvalidArgumentException;
use LogicException;

class ProfileColumnMap
{
    public function get(string $key): string
    {
        $value = match ($key) {
            'avatar' => config('filament-complete-user-profile.columns.avatar'),
            'locale' => config('filament-complete-user-profile.columns.locale'),
            'mfa_secret' => config('filament-complete-user-profile.columns.mfa.secret'),
            'mfa_recovery_codes' => config('filament-complete-user-profile.columns.mfa.recovery_codes'),
            'mfa_email_enabled' => config(
                'filament-complete-user-profile.columns.mfa.email_enabled',
                'has_email_authentication',
            ),
            default => throw new InvalidArgumentException("Unknown profile storage key [{$key}]."),
        };

        if (! is_string($value) || $value === '') {
            throw new LogicException("Profile storage key [{$key}] does not have a valid column configured.");
        }

        return $value;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return [
            'avatar' => $this->get('avatar'),
            'locale' => $this->get('locale'),
            'mfa_secret' => $this->get('mfa_secret'),
            'mfa_recovery_codes' => $this->get('mfa_recovery_codes'),
            'mfa_email_enabled' => $this->get('mfa_email_enabled'),
        ];
    }
}
