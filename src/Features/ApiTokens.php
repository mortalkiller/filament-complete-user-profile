<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;

class ApiTokens extends AbstractFeature
{
    protected int $sort = 50;

    /** @var array<string, string> */
    protected array $abilities = [];

    protected ?int $defaultExpiration = null;

    protected ?int $maxExpiration = null;

    protected bool $tenantScoped = false;

    public function getId(): string
    {
        return 'api-tokens';
    }

    /** @param array<string, string> $abilities */
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

    public function tenantScoped(bool $condition = true): static
    {
        $this->tenantScoped = $condition;

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

    public function isTenantScoped(): bool
    {
        return $this->tenantScoped;
    }

    public function getRequirementIssue(Authenticatable $user): ?string
    {
        if ($this->isEnabled() === false) {
            return null;
        }

        if (class_exists('Laravel\\Sanctum\\Sanctum') === false) {
            return 'Laravel Sanctum must be installed to enable API token management.';
        }

        if (method_exists($user, 'createToken') === false || method_exists($user, 'tokens') === false) {
            return 'The authenticated user model must use Laravel\\Sanctum\\HasApiTokens when API token management is enabled.';
        }

        if ($this->abilities === []) {
            return 'Configure at least one allowed API token ability before enabling API token management.';
        }

        if ($this->tenantScoped) {
            if (
                Schema::hasTable('personal_access_tokens') === false
                || Schema::hasColumn('personal_access_tokens', 'context_type') === false
                || Schema::hasColumn('personal_access_tokens', 'context_id') === false
            ) {
                return 'Publish and run the filament-complete-user-profile token-context migration before enabling tenant-scoped API tokens.';
            }

            if (app()->bound(TokenContextResolver::class) === false) {
                return 'Bind '.TokenContextResolver::class.' before enabling tenant-scoped API tokens.';
            }
        }

        return null;
    }
}
