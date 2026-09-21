<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Auth\Authenticatable;

interface Reauthentication
{
    public function isAvailable(Authenticatable $user): bool;

    /** @return array<Component> */
    public function getFormSchema(): array;

    /** @param array<string, mixed> $data */
    public function confirm(Authenticatable $user, array $data): void;
}
