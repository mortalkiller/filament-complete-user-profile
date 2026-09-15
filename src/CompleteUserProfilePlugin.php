<?php

namespace Mortalkiller\FilamentCompleteUserProfile;

use Closure;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Overview;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Http\Middleware\SetUserLocale;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;

class CompleteUserProfilePlugin implements Plugin
{
    /** @var array<string, ProfileFeature> */
    protected array $features = [];

    public function __construct()
    {
        $this->features = [
            'overview' => Overview::make()->enabled(),
            'profile' => Profile::make()->enabled(),
            'security' => Security::make()->enabled()->password(),
            'sessions' => Sessions::make()->enabled(false),
            'api-tokens' => ApiTokens::make()->enabled(false),
        ];
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-complete-user-profile';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->profile(CompleteUserProfile::class, isSimple: false)
            ->authMiddleware([SetUserLocale::class]);

        $security = $this->getFeature('security');

        if ($security instanceof Security && $security->hasMultiFactorAuthentication()) {
            $panel->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
            ]);
        }
    }

    public function boot(Panel $panel): void {}

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament()->getCurrentOrDefaultPanel()->getPlugin('filament-complete-user-profile');

        return $plugin;
    }

    public function overview(bool|Closure $condition = true): static
    {
        return $this->configureFeature('overview', $condition);
    }

    public function profile(bool|Closure $condition = true): static
    {
        return $this->configureFeature('profile', $condition);
    }

    public function security(bool|Closure $condition = true): static
    {
        return $this->configureFeature('security', $condition);
    }

    public function sessions(bool|Closure $condition = true): static
    {
        return $this->configureFeature('sessions', $condition);
    }

    public function apiTokens(bool|Closure $condition = true): static
    {
        return $this->configureFeature('api-tokens', $condition);
    }

    public function overviewWith(?Closure $configure = null): static
    {
        return $this->configureTypedFeature('overview', $configure);
    }

    public function profileWith(?Closure $configure = null): static
    {
        return $this->configureTypedFeature('profile', $configure);
    }

    public function securityWith(?Closure $configure = null): static
    {
        return $this->configureTypedFeature('security', $configure);
    }

    public function sessionsWith(?Closure $configure = null): static
    {
        return $this->configureTypedFeature('sessions', $configure);
    }

    public function apiTokensWith(?Closure $configure = null): static
    {
        return $this->configureTypedFeature('api-tokens', $configure);
    }

    public function multiFactorAuthentication(bool|Closure $condition = true): static
    {
        $security = $this->getFeature('security');

        if ($security instanceof Security) {
            $security->multiFactorAuthentication($condition);
        }

        return $this;
    }

    public function getFeature(string $id): ProfileFeature
    {
        return $this->features[$id];
    }

    /** @return array<string, ProfileFeature> */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /** @return array<string, ProfileFeature> */
    public function getVisibleFeatures(): array
    {
        $features = array_filter(
            $this->features,
            static fn (ProfileFeature $feature): bool => $feature->isEnabled() && $feature->isVisible(),
        );

        uasort(
            $features,
            static fn (ProfileFeature $first, ProfileFeature $second): int => $first->getSort() <=> $second->getSort(),
        );

        return $features;
    }

    protected function configureFeature(string $id, bool|Closure $condition): static
    {
        $feature = $this->getFeature($id);

        if ($condition instanceof Closure) {
            $feature->enabled();
            $configured = $condition($feature);

            if ($configured instanceof ProfileFeature) {
                $this->features[$id] = $configured;
            }

            return $this;
        }

        $feature->enabled($condition);

        return $this;
    }

    protected function configureTypedFeature(string $id, ?Closure $configure): static
    {
        $feature = $this->getFeature($id);
        $feature->enabled();

        if ($configure !== null) {
            $configured = $configure($feature);

            if ($configured instanceof ProfileFeature) {
                $this->features[$id] = $configured;
            }
        }

        return $this;
    }
}
