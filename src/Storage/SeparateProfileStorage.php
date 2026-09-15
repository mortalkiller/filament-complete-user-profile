<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Models\StoredProfile;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;

class SeparateProfileStorage implements ProfileStorage
{
    public function __construct(
        protected ProfileColumnMap $columns,
    ) {}

    public function get(Authenticatable $user, string $key): mixed
    {
        $profile = $this->queryFor($user)->first();

        return $profile?->getAttribute($this->columns->get($key));
    }

    public function put(Authenticatable $user, string $key, mixed $value): void
    {
        $this->putMany($user, [$key => $value]);
    }

    public function putMany(Authenticatable $user, array $values): void
    {
        $profile = $this->queryFor($user)->firstOrNew();
        $profile->setAttribute('user_type', $this->userType($user));
        $profile->setAttribute('user_id', (string) $user->getAuthIdentifier());

        foreach ($values as $key => $value) {
            $profile->setAttribute($this->columns->get($key), $value);
        }

        $profile->save();
    }

    /** @return Builder<StoredProfile> */
    protected function queryFor(Authenticatable $user): Builder
    {
        return StoredProfile::query()
            ->where('user_type', $this->userType($user))
            ->where('user_id', (string) $user->getAuthIdentifier());
    }

    protected function userType(Authenticatable $user): string
    {
        return $user instanceof Model
            ? $user->getMorphClass()
            : $user::class;
    }
}
