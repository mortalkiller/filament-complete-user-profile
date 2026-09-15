<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;

trait InteractsWithEmailAuthentication
{
    public function initializeInteractsWithEmailAuthentication(): void
    {
        if (config('filament-complete-user-profile.storage', 'user') !== 'user') {
            return;
        }

        $this->emailAuthenticationModel()->mergeCasts([
            app(ProfileColumnMap::class)->get('mfa_email_enabled') => 'boolean',
        ]);
    }

    public function hasEmailAuthentication(): bool
    {
        return (bool) app(ProfileStorage::class)->get(
            $this->emailAuthenticationUser(),
            'mfa_email_enabled',
        );
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        app(ProfileStorage::class)->put(
            $this->emailAuthenticationUser(),
            'mfa_email_enabled',
            $condition,
        );
    }

    protected function emailAuthenticationUser(): Authenticatable
    {
        if (! $this instanceof Authenticatable) {
            throw new LogicException('The email authentication trait requires an authenticatable model.');
        }

        return $this;
    }

    protected function emailAuthenticationModel(): Model
    {
        if (! $this instanceof Model) {
            throw new LogicException('The email authentication trait requires an Eloquent model.');
        }

        return $this;
    }
}
