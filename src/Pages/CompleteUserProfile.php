<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Pages;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;

class CompleteUserProfile extends EditProfile
{
    public function getView(): string
    {
        return 'filament-complete-user-profile::pages.complete-user-profile';
    }

    public static function getLabel(): string
    {
        return __('filament-complete-user-profile::profile.page.label');
    }

    public function getHeading(): string|Htmlable
    {
        return __('filament-complete-user-profile::profile.page.heading');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('filament-complete-user-profile::profile.page.subheading');
    }

    /** @return array<string, ProfileFeature> */
    public function getVisibleFeatures(): array
    {
        return CompleteUserProfilePlugin::get()->getVisibleFeatures();
    }

    public function getFeatureLabel(ProfileFeature $feature): string
    {
        return __("filament-complete-user-profile::profile.features.{$feature->getId()}.label");
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components(array_map(
            fn (ProfileFeature $feature): Component => $this->getFeatureContentComponent($feature),
            $this->getVisibleFeatures(),
        ));
    }

    protected function getFeatureContentComponent(ProfileFeature $feature): Component
    {
        $component = match ($feature->getId()) {
            'profile' => Group::make([$this->getFormContentComponent()]),
            'overview' => Section::make($this->getFeatureLabel($feature))
                ->description(__('filament-complete-user-profile::profile.features.overview.description')),
            'security' => Section::make($this->getFeatureLabel($feature))
                ->description(__('filament-complete-user-profile::profile.features.security.description')),
            'sessions' => Section::make($this->getFeatureLabel($feature))
                ->description(__('filament-complete-user-profile::profile.features.sessions.description')),
            'api-tokens' => Section::make($this->getFeatureLabel($feature))
                ->description(__('filament-complete-user-profile::profile.features.api-tokens.description')),
            default => Section::make($this->getFeatureLabel($feature)),
        };

        return $component->extraAttributes([
            'id' => "account-{$feature->getId()}",
            'data-account-feature' => $feature->getId(),
        ]);
    }
}
