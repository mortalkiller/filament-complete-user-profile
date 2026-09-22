<?php

namespace Mortalkiller\FilamentCompleteUserProfile;

use Closure;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\ProfileFeature;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TenancyResolver;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Overview;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Http\Middleware\SetUserLocale;
use Mortalkiller\FilamentCompleteUserProfile\Pages\CompleteUserProfile;
use Mortalkiller\FilamentCompleteUserProfile\Security\EmailAuthentication\EmailAuthentication;
use Mortalkiller\FilamentCompleteUserProfile\Tenancy\TenancyManager;
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;

class CompleteUserProfilePlugin implements Plugin
{
    /** @var array<string, ProfileFeature> */
    protected array $features = [];

    /** @var array<string, AccountSection> */
    protected array $sections = [];

    protected PageHeaderPlugin|Closure|null $pageHeader = null;

    /** @var TenancyResolver|Closure(): (Model|null)|class-string<TenancyResolver>|null */
    protected TenancyResolver|Closure|string|null $tenancyResolver = null;

    protected ?PageHeaderPlugin $registeredPageHeader = null;

    protected ?Panel $pageHeaderPanel = null;

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
        if ($this->tenancyResolver !== null) {
            app(TenancyManager::class)->useResolver($this->tenancyResolver);
        }

        $panel
            ->profile(CompleteUserProfile::class, isSimple: false)
            ->authMiddleware([SetUserLocale::class]);

        if (! $panel->hasPlugin(PageHeaderPlugin::ID)) {
            $pageHeader = $this->makePageHeaderPlugin();

            $panel->plugin($pageHeader);

            $this->registeredPageHeader = $pageHeader;
            $this->pageHeaderPanel = $panel;
        }

        $security = $this->getFeature('security');

        if (! $security instanceof Security) {
            return;
        }

        $providers = [];

        if ($security->hasAppAuthentication()) {
            $providers[] = AppAuthentication::make()->recoverable();
        }

        if ($security->hasEmailAuthentication()) {
            $providers[] = EmailAuthentication::make();
        }

        if ($providers !== []) {
            $panel->multiFactorAuthentication($providers);
        }
    }

    public function boot(Panel $panel): void {}

    public static function get(): static
    {
        $panel = filament()->getCurrentOrDefaultPanel();

        if ($panel === null) {
            throw new LogicException('No Filament panel is available to resolve the complete user profile plugin.');
        }

        /** @var static $plugin */
        $plugin = $panel->getPlugin('filament-complete-user-profile');

        return $plugin;
    }

    public function pageHeader(PageHeaderPlugin|Closure $plugin): static
    {
        $this->pageHeader = $plugin;

        return $this;
    }

    /**
     * @param  TenancyResolver|Closure(): (Model|null)|class-string<TenancyResolver>  $resolver
     */
    public function tenancyResolver(TenancyResolver|Closure|string $resolver): static
    {
        if (is_string($resolver) && ! is_a($resolver, TenancyResolver::class, true)) {
            throw new LogicException("Tenancy resolver [{$resolver}] must implement ".TenancyResolver::class.'.');
        }

        $this->tenancyResolver = $resolver;

        return $this;
    }

    public function hasCustomTenancyResolver(): bool
    {
        return $this->tenancyResolver !== null;
    }

    /** @return TenancyResolver|Closure(): (Model|null)|class-string<TenancyResolver>|null */
    public function getTenancyResolver(): TenancyResolver|Closure|string|null
    {
        return $this->tenancyResolver;
    }

    public function hasRegisteredPageHeader(): bool
    {
        if ($this->registeredPageHeader === null || $this->pageHeaderPanel === null) {
            return false;
        }

        return $this->pageHeaderPanel->hasPlugin(PageHeaderPlugin::ID)
            && $this->pageHeaderPanel->getPlugin(PageHeaderPlugin::ID) === $this->registeredPageHeader;
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

    public function section(AccountSection $section): static
    {
        $id = $section->getId();

        if (array_key_exists($id, $this->features)) {
            throw new LogicException("Account section [{$id}] uses a reserved built-in feature ID.");
        }

        if (array_key_exists($id, $this->sections)) {
            throw new LogicException("Account section [{$id}] is already registered.");
        }

        $this->sections[$id] = $section;

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

    /** @return array<string, AccountSection> */
    public function getSections(): array
    {
        return $this->sections;
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

    /** @return array<string, AccountSection> */
    public function getVisibleSections(): array
    {
        $sections = array_filter(
            $this->sections,
            static fn (AccountSection $section): bool => $section->isVisible(),
        );

        uasort(
            $sections,
            static fn (AccountSection $first, AccountSection $second): int => $first->getSort() <=> $second->getSort(),
        );

        return $sections;
    }

    protected function makePageHeaderPlugin(): PageHeaderPlugin
    {
        if ($this->pageHeader instanceof PageHeaderPlugin) {
            return $this->pageHeader;
        }

        $plugin = PageHeaderPlugin::make();

        if ($this->pageHeader instanceof Closure) {
            $configured = ($this->pageHeader)($plugin);

            if ($configured instanceof PageHeaderPlugin) {
                return $configured;
            }
        }

        return $plugin;
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
}
