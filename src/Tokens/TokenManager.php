<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tokens;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;

class TokenManager
{
    /** @param array<int, string> $abilities */
    public function create(
        Authenticatable $user,
        ApiTokens $feature,
        string $name,
        array $abilities,
        ?int $expirationDays = null,
    ): mixed {
        if (class_exists('Laravel\\Sanctum\\Sanctum') === false) {
            throw ValidationException::withMessages([
                'tokens' => 'Laravel Sanctum must be installed to create API tokens.',
            ]);
        }

        $relation = $this->relationFor($user);
        $context = null;

        if ($feature->isTenantScoped()) {
            $this->ensureContextColumns($relation);
            $context = $this->resolveManagementContext();
        }

        $allowedAbilities = array_keys($feature->getAbilities());

        if ($allowedAbilities === []) {
            throw ValidationException::withMessages([
                'abilities' => 'Configure at least one allowed API token ability before creating tokens.',
            ]);
        }

        $abilities = array_values(array_unique($abilities));

        if (in_array('*', $abilities, true) || array_diff($abilities, $allowedAbilities) !== []) {
            throw ValidationException::withMessages([
                'abilities' => 'One or more selected API token abilities are not allowed.',
            ]);
        }

        $expirationDays ??= $feature->getDefaultExpiration();
        $maxExpiration = $feature->getMaxExpiration();

        if ($expirationDays !== null && $expirationDays < 1) {
            throw ValidationException::withMessages([
                'expiration' => 'API token expiration must be at least one day.',
            ]);
        }

        if ($expirationDays !== null && $maxExpiration !== null && $expirationDays > $maxExpiration) {
            throw ValidationException::withMessages([
                'expiration' => 'API token expiration exceeds the configured maximum.',
            ]);
        }

        $createToken = [$user, 'createToken'];

        if (is_callable($createToken) === false) {
            throw ValidationException::withMessages([
                'tokens' => 'The authenticated user model must use Laravel\\Sanctum\\HasApiTokens.',
            ]);
        }

        $newToken = $createToken(
            $name,
            $abilities,
            $expirationDays === null ? null : now()->addDays($expirationDays),
        );

        if ($context !== null) {
            $accessToken = data_get($newToken, 'accessToken');

            if ($accessToken instanceof Model === false) {
                throw new LogicException('Sanctum did not return a persisted access token model.');
            }

            $accessToken->forceFill([
                'context_type' => $context->type,
                'context_id' => $context->id,
            ])->save();
        }

        return $newToken;
    }

    /** @return Collection<int, Model> */
    public function tokensFor(Authenticatable $user, ApiTokens $feature): Collection
    {
        $relation = $this->relationFor($user);
        $query = $relation->getQuery();

        if ($feature->isTenantScoped()) {
            $this->ensureContextColumns($relation);
            $context = $this->resolveManagementContext();

            $query
                ->where('context_type', $context->type)
                ->where('context_id', $context->id);
        }

        return $query->get();
    }

    public function revoke(Authenticatable $user, string $tokenId, ?ApiTokens $feature = null): void
    {
        $relation = $this->relationFor($user);
        $query = $relation->getQuery()->whereKey($tokenId);

        if ($feature?->isTenantScoped()) {
            $this->ensureContextColumns($relation);
            $context = $this->resolveManagementContext();

            $query
                ->where('context_type', $context->type)
                ->where('context_id', $context->id);
        }

        $query->delete();
    }

    /** @return MorphMany<Model, Model> */
    protected function relationFor(Authenticatable $user): MorphMany
    {
        $tokens = [$user, 'tokens'];

        if (is_callable($tokens) === false) {
            throw ValidationException::withMessages([
                'tokens' => 'The authenticated user model must use Laravel\\Sanctum\\HasApiTokens.',
            ]);
        }

        $relation = $tokens();

        if ($relation instanceof MorphMany === false) {
            throw ValidationException::withMessages([
                'tokens' => 'The authenticated user model must expose the Sanctum tokens relationship.',
            ]);
        }

        /** @var MorphMany<Model, Model> $relation */
        return $relation;
    }

    /** @param MorphMany<Model, Model> $relation */
    protected function ensureContextColumns(MorphMany $relation): void
    {
        $related = $relation->getRelated();
        $schema = $related->getConnection()->getSchemaBuilder();
        $table = $related->getTable();

        if (
            $schema->hasColumn($table, 'context_type') === false
            || $schema->hasColumn($table, 'context_id') === false
        ) {
            throw ValidationException::withMessages([
                'tokens' => 'Publish and run the filament-complete-user-profile token-context migration before using tenant-scoped API tokens.',
            ]);
        }
    }

    protected function resolveManagementContext(): TokenContext
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Model === false) {
            throw ValidationException::withMessages([
                'tokens' => 'An active Filament tenant is required for tenant-scoped API tokens.',
            ]);
        }

        return TokenContext::fromModel($tenant);
    }
}
