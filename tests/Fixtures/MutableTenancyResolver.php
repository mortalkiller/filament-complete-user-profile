<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver;

class MutableTenancyResolver implements TenancyResolver
{
    public function __construct(
        public ?Model $tenant = null,
    ) {}

    public function resolve(): ?Model
    {
        return $this->tenant;
    }
}
