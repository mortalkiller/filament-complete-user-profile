<?php

declare(strict_types=1);

use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfileServiceProvider;
use Workbench\App\Providers\DemoPanelProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

return [
    CompleteUserProfileServiceProvider::class,
    WorkbenchServiceProvider::class,
    DemoPanelProvider::class,
];
