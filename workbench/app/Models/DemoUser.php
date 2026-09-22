<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class DemoUser extends Authenticatable implements FilamentUser, HasAvatar
{
    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return app()->environment('local', 'testing');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return '/avatar.svg';
    }
}
