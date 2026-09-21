<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Closure;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication as FilamentHasEmailAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

class Security extends AbstractFeature
{
    protected int $sort = 30;

    protected bool|Closure $password = true;

    protected bool|Closure $appAuthentication = false;

    protected bool|Closure $emailAuthentication = false;

    public function getId(): string
    {
        return 'security';
    }

    public function password(bool|Closure $value = true): static
    {
        $this->password = $value;

        return $this;
    }

    public function appAuthentication(bool|Closure $value = true): static
    {
        $this->appAuthentication = $value;

        return $this;
    }

    public function emailAuthentication(bool|Closure $value = true): static
    {
        $this->emailAuthentication = $value;

        return $this;
    }

    public function hasPassword(): bool
    {
        return (bool) $this->evaluate($this->password);
    }

    public function hasAppAuthentication(): bool
    {
        return (bool) $this->evaluate($this->appAuthentication);
    }

    public function hasEmailAuthentication(): bool
    {
        return (bool) $this->evaluate($this->emailAuthentication);
    }

    public function getAppAuthenticationRequirementIssue(Authenticatable $user): ?string
    {
        if (! $this->hasAppAuthentication()) {
            return null;
        }

        if ($user instanceof HasMultiFactorAuthentication) {
            return null;
        }

        return static::translate(
            'filament-complete-user-profile::profile.security.mfa.requirement',
            ['contract' => HasMultiFactorAuthentication::class],
        );
    }

    public function getEmailAuthenticationRequirementIssue(Authenticatable $user): ?string
    {
        if (! $this->hasEmailAuthentication()) {
            return null;
        }

        if (! $user instanceof FilamentHasEmailAuthentication) {
            return static::translate(
                'filament-complete-user-profile::profile.security.email_authentication.requirement',
                ['contract' => FilamentHasEmailAuthentication::class],
            );
        }

        if (! $user instanceof Model || ! method_exists($user, 'notify')) {
            return static::translate(
                'filament-complete-user-profile::profile.security.email_authentication.notifications',
            );
        }

        return null;
    }

    /** @param array<string, scalar> $replace */
    protected static function translate(string $key, array $replace = []): string
    {
        $translation = __($key, $replace);

        return is_string($translation) ? $translation : $key;
    }
}
