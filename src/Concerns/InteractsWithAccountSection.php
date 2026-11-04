<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Concerns;

use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\Support\AccountPresentation;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @mixin Page */
trait InteractsWithAccountSection
{
    use HasPageHeader;

    public function bootInteractsWithAccountSection(): void
    {
        $this->getAccountSection();
    }

    protected function getAccountSection(): AccountSection
    {
        $section = AccountPresentation::sectionForPage(static::class);
        if ($section === null) {
            throw new NotFoundHttpException;
        }

        return $section;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getAccountSection()->getLabel();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->getAccountSection()->getDescription();
    }

    /** @return list<NavigationItem> */
    public function getSubNavigation(): array
    {
        return AccountPresentation::navigation(
            AccountPresentation::items(),
            $this->getAccountSection()->getId(),
            AccountPresentation::label(...),
        );
    }

    public function headerSchema(Schema $schema): Schema
    {
        return $schema->components([
            AccountPresentation::header(
                $this,
                fn (): ?string => AccountPresentation::avatar(AccountPresentation::user()),
                fn (): string => (string) AccountPresentation::user()->getAttribute('name'),
            ),
        ]);
    }

    /** @return array<array-key, string|Htmlable> */
    public function getBreadcrumbs(): array
    {
        return [
            (string) filament()->getProfileUrl() => AccountPresentation::translate('filament-complete-user-profile::profile.page.label'),
            $this->getAccountSection()->getLabel(),
        ];
    }
}
