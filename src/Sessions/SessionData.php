<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Sessions;

use Carbon\CarbonImmutable;

final readonly class SessionData
{
    public function __construct(
        public string $id,
        public string $device,
        public ?string $ipAddress,
        public CarbonImmutable $lastActivity,
        public bool $current,
    ) {}
}
