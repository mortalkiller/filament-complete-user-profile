<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Laravel\Sanctum\HasApiTokens;

class TokenUser extends User
{
    use HasApiTokens;
}
