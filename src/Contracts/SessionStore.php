<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Mortalkiller\FilamentCompleteUserProfile\Sessions\SessionData;

interface SessionStore
{
    /** @return Collection<int, SessionData> */
    public function sessionsFor(Authenticatable $user): Collection;

    public function revoke(Authenticatable $user, string $sessionId): void;

    public function revokeOthers(Authenticatable $user, string $currentSessionId): void;

    public function isSupported(): bool;

    public function getUnsupportedReason(): ?string;
}
