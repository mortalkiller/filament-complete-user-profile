<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Closure;

class Profile extends AbstractFeature
{
    protected int $sort = 20;

    protected bool|Closure $avatar = true;

    protected bool|Closure $name = true;

    protected bool|Closure $email = true;

    protected bool|Closure $locale = true;

    public function getId(): string
    {
        return 'profile';
    }

    public function avatar(bool|Closure $value = true): static
    {
        $this->avatar = $value;

        return $this;
    }

    public function name(bool|Closure $value = true): static
    {
        $this->name = $value;

        return $this;
    }

    public function email(bool|Closure $value = true): static
    {
        $this->email = $value;

        return $this;
    }

    public function locale(bool|Closure $value = true): static
    {
        $this->locale = $value;

        return $this;
    }

    public function hasAvatar(): bool
    {
        return (bool) $this->evaluate($this->avatar);
    }

    public function hasName(): bool
    {
        return (bool) $this->evaluate($this->name);
    }

    public function hasEmail(): bool
    {
        return (bool) $this->evaluate($this->email);
    }

    public function hasLocale(): bool
    {
        return (bool) $this->evaluate($this->locale);
    }
}
