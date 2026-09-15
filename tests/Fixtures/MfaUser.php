<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

class MfaUser extends User implements HasMultiFactorAuthentication
{
    use InteractsWithMultiFactorAuthentication;
}
