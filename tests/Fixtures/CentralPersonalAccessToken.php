<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Laravel\Sanctum\PersonalAccessToken;

class CentralPersonalAccessToken extends PersonalAccessToken
{
    protected $connection = 'tokens';
}
