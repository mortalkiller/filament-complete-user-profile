<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
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
        $never = static::translate('filament-complete-user-profile::profile.api_tokens.never');

        return $table
            ->records(fn (): array => $this->getTokenRecords())
            ->columns([
                TextColumn::make('name')
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.columns.name')),
                TextColumn::make('abilities')
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.columns.permissions'))
                    ->badge(),
                TextColumn::make('last_used_at')
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.columns.last_used'))
                    ->dateTime()
                    ->placeholder($never),
                TextColumn::make('expires_at')
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.columns.expires'))
                    ->dateTime()
                    ->placeholder($never),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.actions.create'))
                    ->schema([
                        TextInput::make('name')
                            ->label(static::translate('filament-complete-user-profile::profile.api_tokens.fields.name'))
                            ->required()
                            ->maxLength(255),
                        CheckboxList::make('abilities')
                            ->label(static::translate('filament-complete-user-profile::profile.api_tokens.fields.permissions'))
                            ->options($feature->getAbilities())
                            ->required()
                            ->columns(1),
                        TextInput::make('expiration')
                            ->label(static::translate('filament-complete-user-profile::profile.api_tokens.fields.expiration'))
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
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.actions.revoke'))
                    ->requiresConfirmation()
                    ->action(function (array $record): void {
                        app(TokenManager::class)->revoke(
                            $this->user(),
                            (string) ($record['id'] ?? ''),
                            $this->feature(),
                        );
                        $this->resetTable();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(static::translate('filament-complete-user-profile::profile.api_tokens.empty'));
    }

    public function showCreatedTokenAction(): Action
    {
        return Action::make('showCreatedToken')
            ->modalHeading(static::translate('filament-complete-user-profile::profile.api_tokens.created.heading'))
            ->fillForm(fn (): array => ['plain_text_token' => $this->createdPlainTextToken])
            ->schema([
                Callout::make(static::translate('filament-complete-user-profile::profile.api_tokens.created.warning.heading'))
                    ->description(static::translate('filament-complete-user-profile::profile.api_tokens.created.description'))
                    ->warning(),
                TextInput::make('plain_text_token')
                    ->label(static::translate('filament-complete-user-profile::profile.api_tokens.created.token'))
                    ->readOnly()
                    ->password()
                    ->copyable()
                    ->revealable(),
            ])
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalSubmitActionLabel(static::translate('filament-complete-user-profile::profile.api_tokens.actions.done'))
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
        return app(TokenManager::class)
            ->tokensFor($this->user(), $this->feature())
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

    protected static function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }
}
