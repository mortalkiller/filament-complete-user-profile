<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileStorage;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use SensitiveParameter;

class CompleteUserProfile extends EditProfile
{
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
        return static::translate('filament-complete-user-profile::profile.page.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return static::translate('filament-complete-user-profile::profile.page.subheading');
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

    public function content(Schema $schema): Schema
    {
        return $schema->components(array_map(
            fn (ProfileFeature $feature): Component => $this->getFeatureContentComponent($feature),
            $this->getVisibleFeatures(),
        ));
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

    protected function getFeatureContentComponent(ProfileFeature $feature): Component
    {
        $component = match ($feature->getId()) {
            'profile' => Section::make($this->getFeatureLabel($feature))
                ->description(static::translate('filament-complete-user-profile::profile.features.profile.description'))
                ->schema([Group::make([$this->getFormContentComponent()])]),
            'overview' => Section::make($this->getFeatureLabel($feature))
                ->description(static::translate('filament-complete-user-profile::profile.features.overview.description')),
            'security' => Section::make($this->getFeatureLabel($feature))
                ->description(static::translate('filament-complete-user-profile::profile.features.security.description')),
            'sessions' => Section::make($this->getFeatureLabel($feature))
                ->description(static::translate('filament-complete-user-profile::profile.features.sessions.description')),
            'api-tokens' => Section::make($this->getFeatureLabel($feature))
                ->description(static::translate('filament-complete-user-profile::profile.features.api-tokens.description')),
            default => Section::make($this->getFeatureLabel($feature)),
        };

        return $component->extraAttributes([
            'id' => "account-{$feature->getId()}",
            'data-account-feature' => $feature->getId(),
        ]);
    }

    protected function getProfileFeature(): Profile
    {
        $feature = CompleteUserProfilePlugin::get()->getFeature('profile');

        if (! $feature instanceof Profile) {
            throw new LogicException('The profile feature must be an instance of '.Profile::class.'.');
        }

        return $feature;
    }

    protected static function translate(string $key): string
    {
        $translation = __($key);

        return is_string($translation) ? $translation : $key;
    }
}
