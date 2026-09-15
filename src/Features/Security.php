<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

class Security extends AbstractFeature
{
    protected int $sort = 30;

    protected bool|Closure $password = true;

    protected bool|Closure $multiFactorAuthentication = false;

    public function getId(): string
    {
        return 'security';
    }

    public function password(bool|Closure $value = true): static
    {
        $this->password = $value;

        return $this;
    }

    public function multiFactorAuthentication(bool|Closure $value = true): static
    {
        $this->multiFactorAuthentication = $value;

        return $this;
    }

    public function hasPassword(): bool
    {
        return (bool) $this->evaluate($this->password);
    }

    public function hasMultiFactorAuthentication(): bool
    {
        return (bool) $this->evaluate($this->multiFactorAuthentication);
    }

    public function getMultiFactorAuthenticationRequirementIssue(Authenticatable $user): ?string
    {
        if (! $this->hasMultiFactorAuthentication()) {
            return null;
        }

        if ($user instanceof HasMultiFactorAuthentication) {
            return null;
        }

        return 'The authenticatable model must implement '.HasMultiFactorAuthentication::class.' when multi-factor authentication is enabled.';
    }
}
