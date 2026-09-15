<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tokens;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;
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
        if (! class_exists('Laravel\\Sanctum\\Sanctum')) {
            throw ValidationException::withMessages([
                'tokens' => 'Laravel Sanctum must be installed to create API tokens.',
            ]);
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

        if (! is_callable($createToken)) {
            throw ValidationException::withMessages([
                'tokens' => 'The authenticated user model must use Laravel\\Sanctum\\HasApiTokens.',
            ]);
        }

        return $createToken(
            $name,
            $abilities,
            $expirationDays === null ? null : now()->addDays($expirationDays),
        );
    }

    public function revoke(Authenticatable $user, string $tokenId): void
    {
        $tokens = [$user, 'tokens'];

        if (! is_callable($tokens)) {
            return;
        }

        $relation = $tokens();

        if ($relation instanceof MorphMany === false) {
            return;
        }

        $relation->getQuery()->whereKey($tokenId)->delete();
    }
}
