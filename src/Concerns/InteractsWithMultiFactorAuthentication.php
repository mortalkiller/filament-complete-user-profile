<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;
use SensitiveParameter;

trait InteractsWithMultiFactorAuthentication
{
    public function initializeInteractsWithMultiFactorAuthentication(): void
    {
        if (config('filament-complete-user-profile.storage', 'user') !== 'user') {
            return;
        }

        $model = $this->multiFactorAuthenticationModel();
        $columns = app(ProfileColumnMap::class);
        $secretColumn = $columns->get('mfa_secret');
        $recoveryCodesColumn = $columns->get('mfa_recovery_codes');

        $model->mergeCasts([
            $secretColumn => 'encrypted',
            $recoveryCodesColumn => 'encrypted:array',
        ]);
        $model->makeHidden([$secretColumn, $recoveryCodesColumn]);
    }

    public function getAppAuthenticationSecret(): ?string
    {
        $secret = app(ProfileStorage::class)->get(
            $this->multiFactorAuthenticationUser(),
            'mfa_secret',
        );

        return is_string($secret) ? $secret : null;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        app(ProfileStorage::class)->put(
            $this->multiFactorAuthenticationUser(),
            'mfa_secret',
            $secret,
        );
    }

    public function getAppAuthenticationHolderName(): string
    {
        return (string) $this->multiFactorAuthenticationModel()->getAttribute('email');
    }

    /** @return array<string>|null */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        $codes = app(ProfileStorage::class)->get(
            $this->multiFactorAuthenticationUser(),
            'mfa_recovery_codes',
        );

        if (! is_array($codes)) {
            return null;
        }

        return array_values(array_filter($codes, is_string(...)));
    }

    /** @param array<string>|null $codes */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        app(ProfileStorage::class)->put(
            $this->multiFactorAuthenticationUser(),
            'mfa_recovery_codes',
            $codes,
        );
    }

    protected function multiFactorAuthenticationUser(): Authenticatable
    {
        if (! $this instanceof Authenticatable) {
            throw new LogicException('The multi-factor authentication trait requires an authenticatable model.');
        }

        return $this;
    }

    protected function multiFactorAuthenticationModel(): Model
    {
        if (! $this instanceof Model) {
            throw new LogicException('The multi-factor authentication trait requires an Eloquent model.');
        }

        return $this;
    }
}
