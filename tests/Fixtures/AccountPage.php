<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Fixtures;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Mortalkiller\FilamentCompleteUserProfile\Concerns\InteractsWithAccountSection;

class AccountPage extends Page
{
    use InteractsWithAccountSection;

    protected static ?string $slug = 'account/billing';

    public int $counter = 0;

    public function mount(): void
    {
        $this->counter = 10;
    }

    public static function canAccess(): bool
    {
        return (bool) config('tests.account_page_access', true);
    }

    public function incrementAction(): Action
    {
        return Action::make('increment')->action(function (): void {
            $this->counter++;
        });
    }

    protected function getHeaderActions(): array
    {
        return [$this->incrementAction()];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([Text::make(fn (): string => "Counter: {$this->counter}")]);
    }
}
