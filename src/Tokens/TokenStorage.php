<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tokens;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Validation\ValidationException;

class TokenStorage
{
    /** @return MorphMany<Model, Model>|null */
    public function relationFor(Authenticatable $user): ?MorphMany
    {
        $tokens = [$user, 'tokens'];

        if (is_callable($tokens) === false) {
            return null;
        }

        $relation = $tokens();

        if ($relation instanceof MorphMany === false) {
            return null;
        }

        /** @var MorphMany<Model, Model> $relation */
        return $relation;
    }

    /** @return MorphMany<Model, Model> */
    public function requireRelation(Authenticatable $user): MorphMany
    {
        $relation = $this->relationFor($user);

        if ($relation === null) {
            throw ValidationException::withMessages([
                'tokens' => 'The authenticated user model must expose the Sanctum tokens relationship.',
            ]);
        }

        return $relation;
    }

    /** @param MorphMany<Model, Model> $relation */
    public function hasContextColumns(MorphMany $relation): bool
    {
        $related = $relation->getRelated();
        $schema = $related->getConnection()->getSchemaBuilder();
        $table = $related->getTable();

        return $schema->hasTable($table)
            && $schema->hasColumn($table, 'context_type')
            && $schema->hasColumn($table, 'context_id');
    }

    /** @param MorphMany<Model, Model> $relation */
    public function ensureContextColumns(MorphMany $relation): void
    {
        if ($this->hasContextColumns($relation)) {
            return;
        }

        throw ValidationException::withMessages([
            'tokens' => 'Publish and run the filament-complete-user-profile token-context migration before using tenant-scoped API tokens.',
        ]);
    }
}
