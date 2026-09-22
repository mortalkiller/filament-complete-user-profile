<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Illuminate\Database\Eloquent\Model;

interface TenancyResolver
{
    public function resolve(): ?Model;
}
