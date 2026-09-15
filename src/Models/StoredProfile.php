<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Models;

use Illuminate\Database\Eloquent\Model;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;

class StoredProfile extends Model
{
    /** @var array<int, string> */
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('filament-complete-user-profile.profile_table', 'filament_user_profiles');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        $columns = app(ProfileColumnMap::class);

        return [
            $columns->get('mfa_secret') => 'encrypted',
            $columns->get('mfa_recovery_codes') => 'encrypted:array',
        ];
    }
}
