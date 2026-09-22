<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Filament\Actions\Action;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\Components\Actions;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Workbench\App\Http\Middleware\LocalDemoUser;
use Workbench\App\Models\DemoUser;
use Workbench\App\Settings\DemoSettings;

final class DemoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('demo')
            ->path('demo')
            ->brandName('Complete User Profile')
            ->spa()
            ->font('sans-serif', provider: LocalFontProvider::class)
            ->colors(['primary' => Color::Indigo])
            ->plugin(
                CompleteUserProfilePlugin::make()
                    ->profile(fn (Profile $profile): Profile => $profile
                        ->locale([
                            'en' => 'English',
                            'pt' => 'Português',
                        ])
                        ->fields([
                            TextInput::make('job_title')
                                ->label('Job title')
                                ->maxLength(120),
                            TextInput::make('phone')
                                ->label('Phone')
                                ->tel()
                                ->maxLength(40),
                        ]))
                    ->security(fn (Security $security): Security => $security
                        ->appAuthentication()
                        ->emailAuthentication())
                    ->sessions()
                    ->apiTokens(fn (ApiTokens $tokens): ApiTokens => $tokens
                        ->abilities([
                            'profile:read' => 'Read profile',
                            'profile:update' => 'Update profile',
                            'security:read' => 'Read security settings',
                        ])
                        ->defaultExpiration(7)
                        ->maxExpiration(30))
                    ->section(
                        AccountSection::make('application-settings')
                            ->label('Application Settings')
                            ->description('Example custom section persisted with spatie/laravel-settings.')
                            ->sort(60)
                            ->schema([
                                TextEntry::make('demo_timezone')
                                    ->label('Timezone')
                                    ->state(fn (): string => app(DemoSettings::class)->timezone),
                                TextEntry::make('demo_weekly_digest')
                                    ->label('Weekly digest')
                                    ->state(fn (): string => app(DemoSettings::class)->weekly_digest ? 'Enabled' : 'Disabled')
                                    ->badge(),
                                Actions::make([
                                    Action::make('editDemoSettings')
                                        ->label('Edit settings')
                                        ->schema([
                                            Select::make('timezone')
                                                ->options([
                                                    'Europe/Lisbon' => 'Europe/Lisbon',
                                                    'Europe/London' => 'Europe/London',
                                                    'America/Toronto' => 'America/Toronto',
                                                ])
                                                ->required(),
                                            Toggle::make('weekly_digest')
                                                ->label('Weekly digest'),
                                        ])
                                        ->fillForm(fn (): array => app(DemoSettings::class)->toArray())
                                        ->action(function (array $data): void {
                                            $settings = app(DemoSettings::class);
                                            $settings->timezone = (string) $data['timezone'];
                                            $settings->weekly_digest = (bool) ($data['weekly_digest'] ?? false);
                                            $settings->save();
                                        }),
                                ]),
                            ]),
                    )
                    ->section(
                        AccountSection::make('addresses')
                            ->label('Addresses')
                            ->description('Example custom section backed by an Eloquent user relationship.')
                            ->sort(70)
                            ->schema([
                                RepeatableEntry::make('demo_addresses')
                                    ->label('Saved addresses')
                                    ->state(function (): array {
                                        $user = filament()->auth()->user();

                                        if (! $user instanceof DemoUser) {
                                            return [];
                                        }

                                        return $user->addresses()
                                            ->get(['label', 'line_one', 'city', 'country_code'])
                                            ->toArray();
                                    })
                                    ->schema([
                                        TextEntry::make('label'),
                                        TextEntry::make('line_one')->label('Address'),
                                        TextEntry::make('city'),
                                        TextEntry::make('country_code')->label('Country'),
                                    ])
                                    ->columns(2),
                                Actions::make([
                                    Action::make('addDemoAddress')
                                        ->label('Add address')
                                        ->schema([
                                            TextInput::make('label')
                                                ->required()
                                                ->maxLength(80),
                                            TextInput::make('line_one')
                                                ->label('Address')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('city')
                                                ->required()
                                                ->maxLength(120),
                                            TextInput::make('country_code')
                                                ->label('Country code')
                                                ->required()
                                                ->length(2),
                                        ])
                                        ->action(function (array $data): void {
                                            $user = filament()->auth()->user();

                                            if (! $user instanceof DemoUser) {
                                                return;
                                            }

                                            $user->addresses()->create($data);
                                        }),
                                ]),
                            ]),
                    ),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                LocalDemoUser::class,
            ], isPersistent: true);
    }
}
