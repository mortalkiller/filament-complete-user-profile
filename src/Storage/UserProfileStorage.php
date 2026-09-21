<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;

class UserProfileStorage implements ProfileStorage
{
    public function __construct(
        protected ProfileColumnMap $columns,
    ) {}

    public function get(Authenticatable $user, string $key): mixed
    {
        return $this->model($user)->getAttribute($this->columns->get($key));
    }

    public function put(Authenticatable $user, string $key, mixed $value): void
    {
        $this->putMany($user, [$key => $value]);
    }

    public function putMany(Authenticatable $user, array $values): void
    {
        $attributes = [];

        foreach ($values as $key => $value) {
            $attributes[$this->columns->get($key)] = $value;
        }

        $this->model($user)->forceFill($attributes)->save();
    }

    protected function model(Authenticatable $user): Model
    {
        if (! $user instanceof Model) {
            throw new LogicException('User profile storage requires an Eloquent authenticatable model.');
        }

        return $user;
    }
}
