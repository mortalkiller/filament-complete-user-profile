<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\Reauthentication;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Sessions\SessionData;
use SensitiveParameter;

class SessionsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $reauthentication = app(Reauthentication::class);
        $currentLabel = static::translate('filament-complete-user-profile::profile.sessions.status.current');

        return $table
            ->records(fn (): array => $this->getSessionRecords())
            ->columns([
                TextColumn::make('device')
                    ->label(static::translate('filament-complete-user-profile::profile.sessions.columns.device')),
                TextColumn::make('ip_address')
                    ->label(static::translate('filament-complete-user-profile::profile.sessions.columns.ip')),
                TextColumn::make('last_activity')
                    ->label(static::translate('filament-complete-user-profile::profile.sessions.columns.last_activity'))
                    ->dateTime(),
                TextColumn::make('status')
                    ->label(static::translate('filament-complete-user-profile::profile.sessions.columns.status'))
                    ->badge()
                    ->color(fn (string $state): string => $state === $currentLabel ? 'success' : 'gray'),
            ])
            ->headerActions([
                Action::make('revokeOtherSessions')
                    ->label(static::translate('filament-complete-user-profile::profile.sessions.actions.revoke_others'))
                    ->requiresConfirmation()
                    ->schema($reauthentication->getFormSchema())
                    ->disabled(fn (): bool => $this->isSupported() === false || $reauthentication->isAvailable($this->user()) === false)
                    ->action(function (array $data): void {
                        $this->revokeOtherSessions($data);
                        $this->resetTable();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label(static::translate('filament-complete-user-profile::profile.sessions.actions.revoke'))
                    ->requiresConfirmation()
                    ->visible(fn (array $record): bool => ($record['current'] ?? false) === false)
                    ->action(function (array $record): void {
                        $this->revokeSession((string) ($record['id'] ?? ''));
                        $this->resetTable();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(static::translate('filament-complete-user-profile::profile.sessions.empty'));
    }

    public function render(): View
    {
        return view('filament-complete-user-profile::livewire.sessions-table');
    }

    public function isSupported(): bool
    {
        return app(SessionStore::class)->isSupported();
    }

    public function getUnsupportedReason(): ?string
    {
        return app(SessionStore::class)->getUnsupportedReason();
    }

    public function revokeSession(string $sessionId): void
    {
        if ($sessionId === '') {
            return;
        }

        app(SessionStore::class)->revoke($this->user(), $sessionId);
    }

    /** @param array<string, mixed> $data */
    public function revokeOtherSessions(#[SensitiveParameter] array $data): void
    {
        $user = $this->user();
        app(Reauthentication::class)->confirm($user, $data);
        app(SessionStore::class)->revokeOthers($user, $this->currentSessionId());
    }

    /** @return array<int, array<string, mixed>> */
    protected function getSessionRecords(): array
    {
        if ($this->isSupported() === false) {
            return [];
        }

        $currentLabel = static::translate('filament-complete-user-profile::profile.sessions.status.current');
        $activeLabel = static::translate('filament-complete-user-profile::profile.sessions.status.active');

        return app(SessionStore::class)
            ->sessionsFor($this->user())
            ->map(static fn (SessionData $session): array => [
                'id' => $session->id,
                'device' => $session->device,
                'ip_address' => $session->ipAddress ?? '—',
                'last_activity' => $session->lastActivity,
                'status' => $session->current ? $currentLabel : $activeLabel,
                'current' => $session->current,
            ])
            ->values()
            ->all();
    }

    protected function user(): Authenticatable
    {
        $user = Filament::auth()->user();

        if (($user instanceof Authenticatable) === false) {
            throw new LogicException('An authenticated Filament user is required to manage browser sessions.');
        }

        return $user;
    }

    protected function currentSessionId(): string
    {
        if (request()->hasSession() === false) {
            throw new LogicException('An active Laravel session is required to revoke other browser sessions.');
        }

        return request()->session()->getId();
    }

    protected static function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }
}
