<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithEmailAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithMultiFactorAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\HasMultiFactorAuthentication;

/**
 * Combines app authentication, email authentication and API tokens so the
 * account security aside can be exercised with every row enabled at once.
 */
class FullSecurityUser extends User implements HasEmailAuthentication, HasMultiFactorAuthentication
{
    use HasApiTokens;
    use InteractsWithEmailAuthentication;
    use InteractsWithMultiFactorAuthentication;
    use Notifiable;
}
