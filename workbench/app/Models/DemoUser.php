<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithEmailAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

class DemoUser extends Authenticatable implements FilamentUser, HasAvatar, HasEmailAuthentication, HasMultiFactorAuthentication
{
    use HasApiTokens;
    use InteractsWithEmailAuthentication;
    use InteractsWithMultiFactorAuthentication;
    use Notifiable;

    protected $table = 'users';

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
