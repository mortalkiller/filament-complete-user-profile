<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\SessionStore;
use Mortalkiller\FilamentCompleteUserProfile\Contracts\TokenContextResolver;
use Mortalkiller\FilamentCompleteUserProfile\Features\ApiTokens;
use Mortalkiller\FilamentCompleteUserProfile\Features\Profile;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Features\Sessions;
use Mortalkiller\FilamentCompleteUserProfile\Support\ProfileColumnMap;
use Mortalkiller\FilamentCompleteUserProfile\Support\UserModelResolver;
use MortalKiller\FilamentPageHeader\PageHeaderPlugin;
use Throwable;

class CheckCompleteUserProfile extends Command
{
    protected $signature = 'filament-complete-user-profile:check';

    protected $description = 'Check Filament Complete User Profile installation and enabled feature requirements.';

    /** @var array<int, array{0: string, 1: string, 2: string}> */
    protected array $checks = [];

    public function handle(): int
    {
        $plugins = $this->registeredPlugins();

        if ($plugins === []) {
            $this->failCheck(
                'Plugin registration',
                'Register CompleteUserProfilePlugin on at least one Filament panel.',
            );

            return $this->finish();
        }

        $this->pass(
            'Plugin registration',
            'Registered on panel(s): '.implode(', ', array_keys($plugins)).'.',
        );

        try {
            $userModel = app(UserModelResolver::class)->resolve();
            $user = app($userModel);
        } catch (Throwable $exception) {
            $this->failCheck('User model', $exception->getMessage());

            return $this->finish();
        }

        $this->pass('User model', $userModel);

        $storageTable = $this->checkProfileStorage($user);
        $this->checkProfileColumns($plugins, $storageTable);
        $this->checkOptionalFeatures($plugins, $user);
        $this->checkPageHeader();

        return $this->finish();
    }

    /** @return array<string, CompleteUserProfilePlugin> */
    protected function registeredPlugins(): array
    {
        $plugins = [];

        foreach (Filament::getPanels() as $panelId => $panel) {
            if (! $panel->hasPlugin('filament-complete-user-profile')) {
                continue;
            }

            $plugin = $panel->getPlugin('filament-complete-user-profile');

            if ($plugin instanceof CompleteUserProfilePlugin) {
                $plugins[(string) $panelId] = $plugin;
            }
        }

        return $plugins;
    }

    protected function checkProfileStorage(Authenticatable $user): ?string
    {
        $storage = config('filament-complete-user-profile.storage', 'user');

        if ($storage === 'user') {
            if (! $user instanceof Model) {
                $this->failCheck('Profile storage', 'User storage requires the authenticatable to be an Eloquent model.');

                return null;
            }

            $table = $user->getTable();

            if (! $user->getConnection()->getSchemaBuilder()->hasTable($table)) {
                $this->failCheck('Profile storage', "User table [{$table}] does not exist. Run the application migrations first.");

                return null;
            }

            $this->pass('Profile storage', "Using configured columns on [{$table}].");

            return $table;
        }

        if ($storage === 'separate') {
            $table = config('filament-complete-user-profile.profile_table', 'filament_user_profiles');

            if (! is_string($table) || $table === '') {
                $this->failCheck('Profile storage', 'Configure a valid filament-complete-user-profile.profile_table value.');

                return null;
            }

            if (! Schema::hasTable($table)) {
                $this->failCheck('Profile storage', "Profile table [{$table}] does not exist. Run the package migrations first.");

                return null;
            }

            $this->pass('Profile storage', "Using separate profile table [{$table}].");

            return $table;
        }

        $this->failCheck('Profile storage', 'Profile storage must be either [user] or [separate].');

        return null;
    }

    /** @param array<string, CompleteUserProfilePlugin> $plugins */
    protected function checkProfileColumns(array $plugins, ?string $table): void
    {
        if ($table === null) {
            return;
        }

        $avatarRequired = false;
        $localeRequired = false;
        $emailMfaRequired = false;

        foreach ($plugins as $plugin) {
            $profile = $plugin->getFeature('profile');

            if ($profile instanceof Profile && $profile->isEnabled()) {
                $avatarRequired = $avatarRequired || $profile->hasAvatar();
                $localeRequired = $localeRequired || $profile->hasLocale();
            }

            $security = $plugin->getFeature('security');

            if ($security instanceof Security && $security->isEnabled()) {
                $emailMfaRequired = $emailMfaRequired || $security->hasEmailAuthentication();
            }
        }

        $columns = app(ProfileColumnMap::class);

        if ($avatarRequired) {
            $this->checkColumn('Avatar column', $table, $columns->get('avatar'));
        } else {
            $this->infoCheck('Avatar column', 'Avatar is disabled on all registered panels.');
        }

        if ($localeRequired) {
            $this->checkColumn('Locale column', $table, $columns->get('locale'));
        } else {
            $this->infoCheck('Locale column', 'Locale is disabled on all registered panels.');
        }

        if ($emailMfaRequired) {
            $this->checkColumn('Email MFA column', $table, $columns->get('mfa_email_enabled'));
        } else {
            $this->infoCheck('Email MFA column', 'Email MFA is disabled on all registered panels.');
        }
    }

    /** @param array<string, CompleteUserProfilePlugin> $plugins */
    protected function checkOptionalFeatures(array $plugins, Authenticatable $user): void
    {
        foreach ($plugins as $panelId => $plugin) {
            $security = $plugin->getFeature('security');

            if ($security instanceof Security && $security->isEnabled() && $security->hasAppAuthentication()) {
                $issue = $security->getAppAuthenticationRequirementIssue($user);
                $this->requirement("Panel [{$panelId}] MFA", $issue, 'Native Filament MFA requirements are satisfied.');
            } else {
                $this->infoCheck("Panel [{$panelId}] MFA", 'Disabled.');
            }

            if ($security instanceof Security && $security->isEnabled() && $security->hasEmailAuthentication()) {
                $issue = $security->getEmailAuthenticationRequirementIssue($user);
                $this->requirement(
                    "Panel [{$panelId}] Email MFA",
                    $issue,
                    'Native Filament email MFA requirements are satisfied.',
                );
            } else {
                $this->infoCheck("Panel [{$panelId}] Email MFA", 'Disabled.');
            }

            $sessions = $plugin->getFeature('sessions');

            if ($sessions instanceof Sessions && $sessions->isEnabled()) {
                $issue = $sessions->getRequirementIssue(app(SessionStore::class));
                $this->requirement("Panel [{$panelId}] Sessions", $issue, 'Database session management is ready.');
            } else {
                $this->infoCheck("Panel [{$panelId}] Sessions", 'Disabled.');
            }

            $tokens = $plugin->getFeature('api-tokens');

            if (! $tokens instanceof ApiTokens || ! $tokens->isEnabled()) {
                $this->infoCheck("Panel [{$panelId}] API Tokens", 'Disabled.');

                continue;
            }

            $issue = $tokens->getRequirementIssue($user);
            $this->requirement("Panel [{$panelId}] API Tokens", $issue, 'Sanctum token requirements are satisfied.');

            if ($tokens->isTenantScoped()) {
                $this->checkTenantTokenInfrastructure($panelId, $user);
            }
        }
    }

    protected function checkPageHeader(): void
    {
        foreach (Filament::getPanels() as $panelId => $panel) {
            if (! $panel->hasPlugin('filament-complete-user-profile')) {
                continue;
            }

            if (! $panel->hasPlugin(PageHeaderPlugin::ID)) {
                $this->failCheck(
                    "Panel [{$panelId}] Page header",
                    'The page header plugin is missing. It is normally registered automatically by CompleteUserProfilePlugin.',
                );

                continue;
            }

            $plugin = $panel->getPlugin(PageHeaderPlugin::ID);

            if (! $plugin instanceof PageHeaderPlugin) {
                $this->failCheck(
                    "Panel [{$panelId}] Page header",
                    'Another plugin has claimed the page header plugin identifier.',
                );

                continue;
            }

            $profilePlugin = $panel->getPlugin('filament-complete-user-profile');
            $mode = $plugin->getOptions()->toArray()['mode'];
            $source = $profilePlugin instanceof CompleteUserProfilePlugin && $profilePlugin->hasRegisteredPageHeader()
                ? 'Registered by this package'
                : 'Registered by the application';

            $this->pass("Panel [{$panelId}] Page header", "{$source}, mode [{$mode}].");
        }
    }

    protected function checkTenantTokenInfrastructure(string $panelId, Authenticatable $user): void
    {
        $tokens = [$user, 'tokens'];
        $relation = is_callable($tokens) ? $tokens() : null;

        if (! $relation instanceof MorphMany) {
            $this->failCheck(
                "Panel [{$panelId}] Token context migration",
                'The user model must expose the Sanctum tokens relationship before tenant token context can be checked.',
            );
        } else {
            $related = $relation->getRelated();
            $schema = $related->getConnection()->getSchemaBuilder();
            $table = $related->getTable();

            if ($schema->hasColumn($table, 'context_type') && $schema->hasColumn($table, 'context_id')) {
                $this->pass("Panel [{$panelId}] Token context migration", "Context columns exist on [{$table}].");
            } else {
                $this->failCheck(
                    "Panel [{$panelId}] Token context migration",
                    'Publish and run the token-context migration: php artisan vendor:publish --tag=filament-complete-user-profile-token-migrations.',
                );
            }
        }

        if (app()->bound(TokenContextResolver::class)) {
            $this->pass("Panel [{$panelId}] TokenContextResolver", 'A TokenContextResolver binding is registered.');
        } else {
            $this->failCheck(
                "Panel [{$panelId}] TokenContextResolver",
                'Bind Mortalkiller\\FilamentCompleteUserProfile\\Contracts\\TokenContextResolver in your application service provider.',
            );
        }
    }

    protected function checkColumn(string $check, string $table, string $column): void
    {
        if (Schema::hasColumn($table, $column)) {
            $this->pass($check, "Column [{$table}.{$column}] exists.");

            return;
        }

        $this->failCheck($check, "Column [{$table}.{$column}] is missing. Run the package migrations first.");
    }

    protected function requirement(string $check, ?string $issue, string $successMessage): void
    {
        if ($issue === null) {
            $this->pass($check, $successMessage);

            return;
        }

        $this->failCheck($check, $issue);
    }

    protected function pass(string $check, string $message): void
    {
        $this->checks[] = ['PASS', $check, $message];
    }

    protected function failCheck(string $check, string $message): void
    {
        $this->checks[] = ['FAIL', $check, $message];
    }

    protected function infoCheck(string $check, string $message): void
    {
        $this->checks[] = ['INFO', $check, $message];
    }

    protected function finish(): int
    {
        $this->table(['Status', 'Check', 'Message'], $this->checks);

        $hasFailures = false;

        foreach ($this->checks as [$status, $check, $message]) {
            if ($status !== 'FAIL') {
                continue;
            }

            $hasFailures = true;
            $this->line("FAIL {$check}: {$message}");
        }

        return $hasFailures ? self::FAILURE : self::SUCCESS;
    }
}
