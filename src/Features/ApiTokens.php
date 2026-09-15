<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Illuminate\Contracts\Auth\Authenticatable;

class ApiTokens extends AbstractFeature
{
    protected int $sort = 50;

    /** @var array<string, string> */
    protected array $abilities = [];

    protected ?int $defaultExpiration = null;

    protected ?int $maxExpiration = null;

    public function getId(): string
    {
        return 'api-tokens';
    }

    /**
     * @param  array<string, string>  $abilities
     */
    public function abilities(array $abilities): static
    {
        $this->abilities = $abilities;

        return $this;
    }

    public function defaultExpiration(?int $days): static
    {
        $this->defaultExpiration = $days;

        return $this;
    }

    public function maxExpiration(?int $days): static
    {
        $this->maxExpiration = $days;

        return $this;
    }

    /** @return array<string, string> */
    public function getAbilities(): array
    {
        return $this->abilities;
    }

    public function getDefaultExpiration(): ?int
    {
        return $this->defaultExpiration;
    }

    public function getMaxExpiration(): ?int
    {
        return $this->maxExpiration;
    }

    public function getRequirementIssue(Authenticatable $user): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! class_exists('Laravel\\Sanctum\\Sanctum')) {
            return 'Laravel Sanctum must be installed to enable API token management.';
        }

        if ((! method_exists($user, 'createToken')) || (! method_exists($user, 'tokens'))) {
            return 'The authenticated user model must use Laravel\\Sanctum\\HasApiTokens when API token management is enabled.';
        }

        if ($this->abilities === []) {
            return 'Configure at least one allowed API token ability before enabling API token management.';
        }

        return null;
    }
}
