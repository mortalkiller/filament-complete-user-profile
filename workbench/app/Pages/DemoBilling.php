<?php

declare(strict_types=1);

namespace Workbench\App\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithAccountSection;

final class DemoBilling extends Page
{
    use InteractsWithAccountSection;

    protected static ?string $slug = 'profile/billing';

    protected Width|string|null $maxContentWidth = Width::SixExtraLarge;

    public int $refreshCount = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshDemo')
                ->label('Refresh demo')
                ->action(function (): void {
                    $this->refreshCount++;
                }),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Demo subscription')->schema([
                Text::make('This routed account page uses local demo data. No payment provider is contacted.'),
                Text::make(fn (): string => "Demo refreshes: {$this->refreshCount}"),
            ]),
        ]);
    }
}
