<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Sessions;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore;
use stdClass;

class DatabaseSessionStore implements SessionStore
{
    /** @return Collection<int, SessionData> */
    public function sessionsFor(Authenticatable $user): Collection
    {
        if (! $this->isSupported()) {
            return collect();
        }

        $currentSessionId = $this->currentSessionId();

        return $this->query()
            ->where('user_id', $user->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (stdClass $record): SessionData => new SessionData(
                id: (string) $record->id,
                device: $this->describeDevice(is_string($record->user_agent) ? $record->user_agent : null),
                ipAddress: is_string($record->ip_address) ? $record->ip_address : null,
                lastActivity: CarbonImmutable::createFromTimestamp((int) $record->last_activity),
                current: ((string) $record->id) === $currentSessionId,
            ))
            ->values();
    }

    public function revoke(Authenticatable $user, string $sessionId): void
    {
        if ((! $this->isSupported()) || ($sessionId === $this->currentSessionId())) {
            return;
        }

        $this->query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', $sessionId)
            ->delete();
    }

    public function revokeOthers(Authenticatable $user, string $currentSessionId): void
    {
        if (! $this->isSupported()) {
            return;
        }

        $this->query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function isSupported(): bool
    {
        if (config('session.driver') !== 'database') {
            return false;
        }

        $table = $this->tableName();
        $connection = $this->connectionName();

        return $connection === null
            ? Schema::hasTable($table)
            : Schema::connection($connection)->hasTable($table);
    }

    public function getUnsupportedReason(): ?string
    {
        if (config('session.driver') !== 'database') {
            return 'Browser session management requires SESSION_DRIVER=database.';
        }

        if (! $this->isSupported()) {
            return 'The configured Laravel sessions table does not exist.';
        }

        return null;
    }

    protected function query(): Builder
    {
        return $this->connection()->table($this->tableName());
    }

    protected function connection(): ConnectionInterface
    {
        return DB::connection($this->connectionName());
    }

    protected function connectionName(): ?string
    {
        $connection = config('session.connection');

        return is_string($connection) && ($connection !== '') ? $connection : null;
    }

    protected function tableName(): string
    {
        $table = config('session.table', 'sessions');

        return is_string($table) && ($table !== '') ? $table : 'sessions';
    }

    protected function currentSessionId(): ?string
    {
        $request = request();

        return $request->hasSession() ? $request->session()->getId() : null;
    }

    protected function describeDevice(?string $userAgent): string
    {
        $userAgent ??= '';

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh'), str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown platform',
        };

        $device = match (true) {
            str_contains($userAgent, 'iPad'), str_contains($userAgent, 'Tablet') => 'Tablet',
            str_contains($userAgent, 'Mobile'), str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'Android') => 'Mobile',
            default => 'Desktop',
        };

        return "{$browser} · {$platform} · {$device}";
    }
}
