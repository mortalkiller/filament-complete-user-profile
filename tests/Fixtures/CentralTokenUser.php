<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Laravel\Sanctum\HasApiTokens;

class CentralTokenUser extends User
{
    use HasApiTokens;

    protected $connection = 'tokens';
}
