<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $guarded = [];

    protected $table = 'users';
}
