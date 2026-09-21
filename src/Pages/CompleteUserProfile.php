<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Navigation\NavigationItem;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Url;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\Reauthentication;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Livewire\ApiTokensTable;
use Mortalkiller\FilamentCompleteUserProfile\Livewire\SessionsTable;
use MortalKiller\FilamentPageHeader\Components\Header;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;
use MortalKiller\FilamentPageHeader\Enums\BreadcrumbPosition;
use SensitiveParameter;

class CompleteUserProfile extends EditProfile
{
    use HasPageHeader;

    #[Url]
    public ?string $section = null;

    /** @var array<string, mixed> */
    protected array $savedProfileData = [];

    public function getView(): string
    {
        return 'filament-complete-user-profile::pages.complete-user-profile';
    }

    public static function getLabel(): string
    {
        return static::translate('filament-complete-user-profile::profile.page.label');
    }

    public function getHeading(): string|Htmlable
    {
        $item = $this->getActiveAccountItem();

        return $item === null
            ? static::translate('filament-complete-user-profile::profile.page.heading')
            : $this->getAccountItemLabel($item);
    }

    public function getSubheading(): string|Htmlable|null
    {
        $item = $this->getActiveAccountItem();

        if ($item === null) {
            return static::translate('filament-complete-user-profile::profile.page.subheading');
        }

        return $this->getAccountItemDescription($item);
    }

    /** @return array<string, ProfileFeature> */
    public function getVisibleFeatures(): array
    {
        return CompleteUserProfilePlugin::get()->getVisibleFeatures();
    }

    public function getFeatureLabel(ProfileFeature $feature): string
    {
        return static::translate("filament-complete-user-profile::profile.features.{$feature->getId()}.label");
    }

    /** @return array<NavigationItem> */
    public function getSubNavigation(): array
    {
        $activeItemId = $this->getActiveAccountItem()?->getId();

        return array_values(array_map(
            function (ProfileFeature|AccountSection $item) use ($activeItemId): NavigationItem {
                $itemId = $item->getId();

                return NavigationItem::make($this->getAccountItemLabel($item))
                    ->key("account-{$itemId}")
                    ->sort($item->getSort())
                    ->url(filament()->getProfileUrl(['section' => $itemId]))
                    ->isActiveWhen(static fn (): bool => $activeItemId === $itemId);
            },
            $this->getVisibleAccountItems(),
        ));
    }

    public function content(Schema $schema): Schema
    {
        $item = $this->getActiveAccountItem();

        return $schema->components(
            $item === null ? [] : [$this->getAccountItemContentComponent($item)],
        );
    }

    public function headerSchema(Schema $schema): Schema
    {
        return $schema->components([
            Header::make()
                ->heading(fn (): string|Htmlable => $this->getHeading())
                ->description(fn (): string|Htmlable|null => $this->getSubheading())
                ->avatar(fn (): ?string => $this->getAccountAvatarUrl())
                ->initials(fn (): string => (string) $this->getUser()->getAttribute('name'))
                ->breadcrumbs(BreadcrumbPosition::Inside)
                ->subNavigation(),
        ]);
    }

    /** @return array<array-key, string|Htmlable> */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            (string) filament()->getProfileUrl() => static::translate('filament-complete-user-profile::profile.page.label'),
        ];

        $item = $this->getActiveAccountItem();

        if ($item !== null) {
            $breadcrumbs[] = $this->getAccountItemLabel($item);
        }

        return $breadcrumbs;
    }

    protected function getAccountAvatarUrl(): ?string
    {
        $avatar = app(ProfileStorage::class)->get($this->getUser(), 'avatar');

        if (is_string($avatar) && $avatar !== '') {
            $disk = config('filament.default_filesystem_disk');
            $filesystem = Storage::disk(is_string($disk) ? $disk : 'public');

            if ($filesystem instanceof Cloud) {
                return $filesystem->url($avatar);
            }
        }

        return filament()->getUserAvatarUrl($this->getUser());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->getProfileFormComponents());
    }

    /** @return array<int, Component> */
    public function getProfileFormComponents(): array
    {
        $feature = $this->getProfileFeature();
        $fields = [];

        if ($feature->hasAvatar()) {
            $fields[] = $feature->configureAvatar(
                FileUpload::make('avatar')
                    ->label(static::translate('filament-complete-user-profile::profile.fields.avatar'))
                    ->avatar()
                    ->image()
                    ->directory('avatars'),
            );
        }

        if ($feature->hasName()) {
            $fields[] = $feature->configureName(parent::getNameFormComponent());
        }

        if ($feature->hasEmail()) {
            $fields[] = $feature->configureEmail(parent::getEmailFormComponent());
        }

        if ($feature->hasLocale()) {
            $fields[] = $feature->configureLocale(
                Select::make('locale')
                    ->label(static::translate('filament-complete-user-profile::profile.fields.locale'))
                    ->options($feature->getLocaleOptions())
                    ->required(),
            );
        }

        $fields = [...$fields, ...$feature->getAdditionalFields()];

        return $feature->modifyFields($fields);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);
        $feature = $this->getProfileFeature();
        $storage = app(ProfileStorage::class);

        if ($feature->hasAvatar()) {
            $data['avatar'] = $storage->get($this->getUser(), 'avatar');
        }

        if ($feature->hasLocale()) {
            $data['locale'] = $storage->get($this->getUser(), 'locale');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        return $this->getProfileFeature()->mutateDataBeforeSave(
            parent::mutateFormDataBeforeSave($data),
        );
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        $feature = $this->getProfileFeature();
        $profileData = [];

        if ($feature->hasAvatar() && array_key_exists('avatar', $data)) {
            $profileData['avatar'] = $data['avatar'];
            unset($data['avatar']);
        }

        if ($feature->hasLocale() && array_key_exists('locale', $data)) {
            $profileData['locale'] = $data['locale'];
            unset($data['locale']);
        }

        $record = parent::handleRecordUpdate($record, $data);

        if ($profileData !== []) {
            app(ProfileStorage::class)->putMany($this->getUser(), $profileData);
        }

        $this->savedProfileData = [...$data, ...$profileData];

        return $record;
    }

    protected function afterSave(): void
    {
        $this->getProfileFeature()->runAfterSave($this->getUser(), $this->savedProfileData);
    }

    /** @param array<string, mixed> $data */
    protected function updatePassword(#[SensitiveParameter] array $data): void
    {
        $user = $this->getUser();
        app(Reauthentication::class)->confirm($user, $data);

        $validated = Validator::make($data, [
            'password' => ['required', 'string', 'confirmed', PasswordRule::default()],
        ])->validate();

        $hashedPassword = Hash::make($validated['password']);

        $user->forceFill(['password' => $hashedPassword])->save();

        if (request()->hasSession()) {
            request()->session()->put(
                'password_hash_'.filament()->getAuthGuard(),
                $hashedPassword,
            );
        }
    }

    /** @return array<string, mixed> */
    protected function getOverviewData(): array
    {
        $user = $this->getUser();
        $storage = app(ProfileStorage::class);
        $data = [
            'avatar' => $storage->get($user, 'avatar'),
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'locale' => $storage->get($user, 'locale'),
        ];

        return array_filter($data, static fn (mixed $value): bool => filled($value));
    }

    /** @return array<int, ProfileFeature|AccountSection> */
    protected function getVisibleAccountItems(): array
    {
        $plugin = CompleteUserProfilePlugin::get();
        $items = [
            ...array_values($plugin->getVisibleFeatures()),
            ...array_values($plugin->getVisibleSections()),
        ];

        usort(
            $items,
            static fn (ProfileFeature|AccountSection $first, ProfileFeature|AccountSection $second): int => $first->getSort() <=> $second->getSort(),
        );

        return $items;
    }

    protected function getAccountItemLabel(ProfileFeature|AccountSection $item): string
    {
        return $item instanceof AccountSection
            ? $item->getLabel()
            : $this->getFeatureLabel($item);
    }

    protected function getAccountItemDescription(ProfileFeature|AccountSection $item): ?string
    {
        if ($item instanceof AccountSection) {
            return $item->getDescription();
        }

        return static::translate("filament-complete-user-profile::profile.features.{$item->getId()}.description");
    }

    protected function getAccountItemContentComponent(ProfileFeature|AccountSection $item): Component
    {
        if ($item instanceof AccountSection) {
            return Section::make($item->getLabel())
                ->schema($item->getSchema());
        }

        return $this->getFeatureContentComponent($item);
    }

    protected function getFeatureContentComponent(ProfileFeature $feature): Component
    {
        return match ($feature->getId()) {
            'profile' => Section::make($this->getFeatureLabel($feature))
                ->schema([Group::make([$this->getFormContentComponent()])]),
            'overview' => $this->getOverviewContentComponent($feature),
            'security' => $this->getSecurityContentComponent($feature),
            'sessions' => $this->getSessionsContentComponent($feature),
            'api-tokens' => $this->getApiTokensContentComponent($feature),
            default => Section::make($this->getFeatureLabel($feature)),
        };
    }

    protected function getOverviewContentComponent(ProfileFeature $feature): Component
    {
        $data = $this->getOverviewData();
        $entries = [];

        if (array_key_exists('avatar', $data)) {
            $entries[] = ImageEntry::make('avatar')
                ->label(static::translate('filament-complete-user-profile::profile.fields.avatar'))
                ->state($data['avatar'])
                ->circular();
        }

        foreach (['name', 'email', 'locale'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $entries[] = TextEntry::make($key)
                ->label(static::translate("filament-complete-user-profile::profile.overview.{$key}"))
                ->state($data[$key]);
        }

        return Section::make($this->getFeatureLabel($feature))
            ->schema($entries);
    }

    protected function getSecurityContentComponent(ProfileFeature $feature): Component
    {
        $security = $this->getSecurityFeature();
        $components = [];

        if ($security->hasPassword()) {
            $components[] = Actions::make([$this->getUpdatePasswordAction()]);
        }

        if ($security->hasAppAuthentication() || $security->hasEmailAuthentication()) {
            $multiFactorAuthentication = $this->getMultiFactorAuthenticationContentComponent();

            if ($multiFactorAuthentication !== null) {
                $components[] = $multiFactorAuthentication;
            }
        }

        return Section::make($this->getFeatureLabel($feature))
            ->schema($components);
    }

    protected function getSessionsContentComponent(ProfileFeature $feature): Component
    {
        return Section::make($this->getFeatureLabel($feature))
            ->schema([
                LivewireComponent::make(SessionsTable::class),
            ]);
    }

    protected function getApiTokensContentComponent(ProfileFeature $feature): Component
    {
        return Section::make($this->getFeatureLabel($feature))
            ->schema([
                LivewireComponent::make(ApiTokensTable::class),
            ]);
    }

    protected function getUpdatePasswordAction(): Action
    {
        $reauthentication = app(Reauthentication::class);

        return Action::make('updatePassword')
            ->label(static::translate('filament-complete-user-profile::profile.security.password.action'))
            ->schema([
                ...$reauthentication->getFormSchema(),
                TextInput::make('password')
                    ->label(static::translate('filament-complete-user-profile::profile.security.password.new'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('new-password')
                    ->rule(PasswordRule::default())
                    ->required(),
                TextInput::make('password_confirmation')
                    ->label(static::translate('filament-complete-user-profile::profile.security.password.confirmation'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('new-password')
                    ->same('password')
                    ->required(),
            ])
            ->disabled(fn (): bool => ! $reauthentication->isAvailable($this->getUser()))
            ->action(function (array $data): void {
                $this->updatePassword($data);
            });
    }

    protected function getProfileFeature(): Profile
    {
        $feature = CompleteUserProfilePlugin::get()->getFeature('profile');

        if (! $feature instanceof Profile) {
            throw new LogicException('The profile feature must be an instance of '.Profile::class.'.');
        }

        return $feature;
    }

    protected function getSecurityFeature(): Security
    {
        $feature = CompleteUserProfilePlugin::get()->getFeature('security');

        if (! $feature instanceof Security) {
            throw new LogicException('The security feature must be an instance of '.Security::class.'.');
        }

        return $feature;
    }

    protected function getActiveAccountItem(): ProfileFeature|AccountSection|null
    {
        $items = $this->getVisibleAccountItems();
        $requestedItemId = $this->section;

        if (is_string($requestedItemId)) {
            foreach ($items as $item) {
                if ($item->getId() === $requestedItemId) {
                    return $item;
                }
            }
        }

        return $items[0] ?? null;
    }

    protected static function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }
}
