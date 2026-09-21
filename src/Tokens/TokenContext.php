<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tokens;

use Illuminate\Database\Eloquent\Model;

final readonly class TokenContext
{
    public function __construct(
        public string $type,
        public string $id,
    ) {}

    public static function fromModel(Model $model): self
    {
        return new self(
            type: $model->getMorphClass(),
            id: (string) $model->getKey(),
        );
    }

    public function matches(Model $model): bool
    {
        return $this->type === $model->getMorphClass()
            && $this->id === (string) $model->getKey();
    }
}
