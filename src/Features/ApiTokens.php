<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Features;

use Illuminate\Contracts\Auth\Authenticatable;
use Mortalkiller\FilamentCompleteUserProfile\Tokens\TokenStorage;

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
            return static::translate('filament-complete-user-profile::profile.api_tokens.requirements.sanctum');
        }

        if (method_exists($user, 'createToken') === false || method_exists($user, 'tokens') === false) {
            return static::translate('filament-complete-user-profile::profile.api_tokens.requirements.user_model');
        }

        $storage = app(TokenStorage::class);
        $relation = $storage->relationFor($user);

        if ($relation === null) {
            return static::translate('filament-complete-user-profile::profile.api_tokens.requirements.user_model');
        }

        if ($this->abilities === []) {
            return static::translate('filament-complete-user-profile::profile.api_tokens.requirements.abilities');
        }

        if ($this->tenantScoped && ! $storage->hasContextColumns($relation)) {
            return static::translate('filament-complete-user-profile::profile.api_tokens.requirements.context_migration');
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
