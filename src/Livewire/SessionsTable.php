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
use LogicException;
use Livewire\Component;
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

        return $table
            ->records(fn (): array => $this->getSessionRecords())
            ->columns([
                TextColumn::make('device')
                    ->label('Device'),
                TextColumn::make('ip_address')
                    ->label('IP'),
                TextColumn::make('last_activity')
                    ->label('Last activity')
                    ->dateTime(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Current' ? 'success' : 'gray'),
            ])
            ->headerActions([
                Action::make('revokeOtherSessions')
                    ->label('Revoke other sessions')
                    ->requiresConfirmation()
                    ->schema($reauthentication->getFormSchema())
                    ->disabled(fn (): bool => (! $this->isSupported()) || (! $reauthentication->isAvailable($this->user())))
                    ->action(function (array $data): void {
                        $this->revokeOtherSessions($data);
                        $this->resetTable();
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->requiresConfirmation()
                    ->visible(fn (array $record): bool => ! (bool) ($record['current'] ?? false))
                    ->action(function (array $record): void {
                        $this->revokeSession((string) ($record['id'] ?? ''));
                        $this->resetTable();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No browser sessions found');
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
        if (! $this->isSupported()) {
            return [];
        }

        return app(SessionStore::class)
            ->sessionsFor($this->user())
            ->map(static fn (SessionData $session): array => [
                'id' => $session->id,
                'device' => $session->device,
                'ip_address' => $session->ipAddress ?? '—',
                'last_activity' => $session->lastActivity,
                'status' => $session->current ? 'Current' : 'Active',
                'current' => $session->current,
            ])
            ->values()
            ->all();
    }

    protected function user(): Authenticatable
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Authenticatable) {
            throw new LogicException('An authenticated Filament user is required to manage browser sessions.');
        }

        return $user;
    }

    protected function currentSessionId(): string
    {
        if (! request()->hasSession()) {
            throw new LogicException('An active Laravel session is required to revoke other browser sessions.');
        }

        return request()->session()->getId();
    }
}
