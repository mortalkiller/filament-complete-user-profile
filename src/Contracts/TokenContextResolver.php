<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TokenContextResolver
{
    public function resolve(): ?Model;
}
