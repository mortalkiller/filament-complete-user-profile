<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Illuminate\Notifications\Notifiable;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithEmailAuthentication;

class EmailMfaUser extends User implements HasEmailAuthentication
{
    use InteractsWithEmailAuthentication;
    use Notifiable;
}
