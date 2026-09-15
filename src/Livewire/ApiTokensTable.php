<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Livewire\Component;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Tokens\TokenManager;
use SensitiveParameter;

class ApiTokensTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected ?string $createdPlainTextToken = null;

    public function table(Table $table): Table
    {
        $feature = $this->feature();

        return $table
            ->records(fn (): array => $this->getTokenRecords())
            ->columns([
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('abilities')
                    ->label('Permissions')
                    ->badge(),
                TextColumn::make('last_used_at')
                    ->label('Last used')
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create token')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        CheckboxList::make('abilities')
                            ->label('Permissions')
                            ->options($feature->getAbilities())
                            ->required()
                            ->columns(1),
                        TextInput::make('expiration')
                            ->label('Expires in days')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue($feature->getMaxExpiration())
                            ->default($feature->getDefaultExpiration()),
                    ])
                    ->disabled(fn (): bool => $feature->getRequirementIssue($this->user()) !== null)
                    ->action(function (array $data): void {
                        $this->createToken($data);
                        $this->replaceMountedAction('showCreatedToken');
                    }),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        app(TokenManager::class)->revoke($this->user(), (string) ($record['id'] ?? ''));
                        $this->resetTable();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No API tokens');
    }

    public function showCreatedTokenAction(): Action
    {
        return Action::make('showCreatedToken')
            ->modalHeading('API token created')
            ->modalDescription('Copy this token now. You will not be able to see it again.')
            ->schema([
                TextEntry::make('plain_text_token')
                    ->label('Token')
                    ->state(fn (): ?string => $this->createdPlainTextToken)
                    ->copyable(),
            ])
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalSubmitActionLabel('Done')
            ->action(fn (): null => $this->dismissCreatedToken());
    }

    /** @param array<string, mixed> $data */
    public function createToken(#[SensitiveParameter] array $data): void
    {
        $name = $data['name'] ?? null;
        $abilities = $data['abilities'] ?? null;
        $expiration = $data['expiration'] ?? null;

        if (is_string($name) === false || is_array($abilities) === false) {
            throw new LogicException('API token creation data is invalid.');
        }

        $abilities = array_values(array_filter($abilities, is_string(...)));
        $expirationDays = is_numeric($expiration) ? (int) $expiration : null;

        $token = app(TokenManager::class)->create(
            $this->user(),
            $this->feature(),
            $name,
            $abilities,
            $expirationDays,
        );

        $plainTextToken = data_get($token, 'plainTextToken');

        if (is_string($plainTextToken) === false || $plainTextToken === '') {
            throw new LogicException('Sanctum did not return a plaintext token after creation.');
        }

        $this->createdPlainTextToken = $plainTextToken;
        $this->resetTable();
    }

    public function dismissCreatedToken(): null
    {
        $this->createdPlainTextToken = null;

        return null;
    }

    public function getCreatedPlainTextToken(): ?string
    {
        return $this->createdPlainTextToken;
    }

    public function getRequirementIssue(): ?string
    {
        return $this->feature()->getRequirementIssue($this->user());
    }

    public function render(): View
    {
        return view('filament-complete-user-profile::livewire.api-tokens-table');
    }

    /** @return array<int, array<string, mixed>> */
    protected function getTokenRecords(): array
    {
        $tokens = [$this->user(), 'tokens'];

        if (is_callable($tokens) === false) {
            return [];
        }

        $relation = $tokens();

        if ($relation instanceof MorphMany === false) {
            return [];
        }

        return $relation->get()
            ->map(static function (Model $token): array {
                $abilities = $token->getAttribute('abilities');

                return [
                    'id' => (string) $token->getKey(),
                    'name' => (string) $token->getAttribute('name'),
                    'abilities' => is_array($abilities) ? array_values($abilities) : [],
                    'last_used_at' => $token->getAttribute('last_used_at'),
                    'expires_at' => $token->getAttribute('expires_at'),
                ];
            })
            ->values()
            ->all();
    }

    protected function feature(): ApiTokens
    {
        $feature = CompleteUserProfilePlugin::get()->getFeature('api-tokens');

        if ($feature instanceof ApiTokens === false) {
            throw new LogicException('The API tokens feature must be an instance of '.ApiTokens::class.'.');
        }

        return $feature;
    }

    protected function user(): Authenticatable
    {
        $user = Filament::auth()->user();

        if ($user instanceof Authenticatable === false) {
            throw new LogicException('An authenticated Filament user is required to manage API tokens.');
        }

        return $user;
    }
}
