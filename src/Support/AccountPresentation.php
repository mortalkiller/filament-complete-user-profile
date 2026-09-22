<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Support;

use Closure;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Enums\BreadcrumbPosition;

/** @internal Shared account presentation; not a public extension contract. */
final class AccountPresentation
{
    /** @return list<ProfileFeature|AccountSection> */
    public static function items(): array
    {
        $plugin = CompleteUserProfilePlugin::get();
        $items = [...array_values($plugin->getVisibleFeatures()), ...array_values($plugin->getVisibleSections())];

        usort($items, static fn (ProfileFeature|AccountSection $a, ProfileFeature|AccountSection $b): int => $a->getSort() <=> $b->getSort());

        return $items;
    }

    /** @param class-string<Page> $page */
    public static function sectionForPage(string $page): ?AccountSection
    {
        $panel = Filament::getCurrentPanel();

        if ($panel === null || ! $panel->hasPlugin('filament-complete-user-profile')) {
            return null;
        }

        foreach (CompleteUserProfilePlugin::get()->getSections() as $section) {
            if ($section->getPage() === $page) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @param  array<ProfileFeature|AccountSection>  $items
     * @param  Closure(ProfileFeature|AccountSection): string  $label
     * @return list<NavigationItem>
     */
    public static function navigation(array $items, ?string $activeId, Closure $label): array
    {
        $navigation = [];
        $panel = Filament::getCurrentOrDefaultPanel();

        foreach ($items as $item) {
            $page = $item instanceof AccountSection ? $item->getPage() : null;

            if ($page !== null && (($panel?->hasTenancy() && Filament::getTenant() === null) || ! $page::canAccess())) {
                continue;
            }

            $id = $item->getId();
            $navigation[] = NavigationItem::make($label($item))
                ->key("account-{$id}")
                ->sort($item->getSort())
                ->url($page === null
                    ? Filament::getProfileUrl(['section' => $id])
                    : $page::getUrl(panel: $panel?->getId(), tenant: Filament::getTenant()))
                ->isActiveWhen(static fn (): bool => $activeId === $id);
        }

        return $navigation;
    }

    public static function label(ProfileFeature|AccountSection $item): string
    {
        return $item instanceof AccountSection ? $item->getLabel() : self::translate("filament-complete-user-profile::profile.features.{$item->getId()}.label");
    }

    public static function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }

    public static function header(Page $page, Closure $avatar, Closure $initials): Header
    {
        return Header::make()
            ->heading(fn () => $page->getHeading())
            ->description(fn () => $page->getSubheading())
            ->avatar($avatar)
            ->initials($initials)
            ->breadcrumbs(BreadcrumbPosition::Inside)
            ->subNavigation();
    }

    public static function user(): Model&Authenticatable
    {
        $user = Filament::auth()->user();

        if (! $user instanceof Model) {
            throw new LogicException('The authenticated account user must be an Eloquent model.');
        }

        return $user;
    }

    public static function avatar(Model&Authenticatable $user): ?string
    {
        $avatar = app(ProfileStorage::class)->get($user, 'avatar');

        if (is_string($avatar) && $avatar !== '') {
            $disk = config('filament.default_filesystem_disk');
            $filesystem = Storage::disk(is_string($disk) ? $disk : 'public');

            if (! $filesystem instanceof Cloud) {
                throw new LogicException('The configured filesystem disk must be able to generate a URL for the stored avatar.');
            }

            return $filesystem->url($avatar);
        }

        return Filament::getUserAvatarUrl($user);
    }
}
