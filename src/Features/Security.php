<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Closure;

class Security extends AbstractFeature
{
    protected int $sort = 30;

    protected bool | Closure $password = true;

    protected bool | Closure $multiFactorAuthentication = false;

    public function getId(): string
    {
        return 'security';
    }

    public function password(bool | Closure $condition = true): static
    {
        $this->password = $condition;

        return $this;
    }

    public function multiFactorAuthentication(bool | Closure $condition = true): static
    {
        $this->multiFactorAuthentication = $condition;

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
}
