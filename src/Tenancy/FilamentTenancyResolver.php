<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tenancy;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;

final class FilamentTenancyResolver implements TokenContextResolver
{
    public function resolve(): ?Model
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Model ? $tenant : null;
    }
}
