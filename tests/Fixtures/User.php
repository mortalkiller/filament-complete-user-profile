<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @var array<int, string> */
    protected $guarded = [];

    /** @var string */
    protected $table = 'users';
}
