<?php

namespace Mortalkiller\FilamentCompleteUserProfile;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use InvalidArgumentException;
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
    protected array $features;

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

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
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
    }

    public function boot(Panel $panel): void
    {
        // Feature-specific panel boot hooks are added by their respective features.
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
            fn (ProfileFeature $feature): bool => $feature->isEnabled() && $feature->isVisible(),
        );

        uasort(
            $features,
            fn (ProfileFeature $first, ProfileFeature $second): int => $first->getSort() <=> $second->getSort(),
        );

        return $features;
    }

    public function getFeature(string $id): ProfileFeature
    {
        return $this->features[$id] ?? throw new InvalidArgumentException("Unknown profile feature [{$id}].");
    }

    /** @param array<int, ProfileFeature> $features */
    public function features(array $features): static
    {
        foreach ($features as $feature) {
            $this->features[$feature->getId()] = $feature;
        }

        return $this;
    }

    public function overview(bool|Closure $configuration = true): static
    {
        $this->configureFeature('overview', $configuration);

        return $this;
    }

    public function profile(bool|Closure $configuration = true): static
    {
        $this->configureFeature('profile', $configuration);

        return $this;
    }

    public function security(bool|Closure $configuration = true): static
    {
        $this->configureFeature('security', $configuration);

        return $this;
    }

    public function sessions(bool|Closure $configuration = true): static
    {
        $this->configureFeature('sessions', $configuration);

        return $this;
    }

    public function apiTokens(bool|Closure $configuration = true): static
    {
        $this->configureFeature('api-tokens', $configuration);

        return $this;
    }

    public function multiFactorAuthentication(bool|Closure $condition = true): static
    {
        $feature = $this->getFeature('security');

        if ($feature instanceof Security === false) {
            throw new InvalidArgumentException('The security feature must be an instance of '.Security::class.'.');
        }

        $feature->enabled()->multiFactorAuthentication($condition);

        return $this;
    }

    protected function configureFeature(string $id, bool|Closure $configuration): void
    {
        $feature = $this->getFeature($id);

        if (is_bool($configuration)) {
            if (method_exists($feature, 'enabled') === false) {
                throw new InvalidArgumentException("Profile feature [{$id}] cannot be toggled.");
            }

            $feature->enabled($configuration);

            return;
        }

        if (method_exists($feature, 'enabled') === false) {
            throw new InvalidArgumentException("Profile feature [{$id}] cannot be configured.");
        }

        $feature->enabled();
        $configuration($feature);
    }
}
