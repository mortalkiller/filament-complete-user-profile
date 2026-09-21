<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;

interface HasMultiFactorAuthentication extends HasAppAuthentication, HasAppAuthenticationRecovery {}
