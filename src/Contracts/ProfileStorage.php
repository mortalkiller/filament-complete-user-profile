<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ProfileStorage
{
    public function get(Authenticatable $user, string $key): mixed;

    public function put(Authenticatable $user, string $key, mixed $value): void;

    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(Authenticatable $user, array $values): void;
}
