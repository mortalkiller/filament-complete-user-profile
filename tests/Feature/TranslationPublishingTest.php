<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class TranslationPublishingTest extends TestCase
{
    private string $languageDirectory;

    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->languageDirectory = sys_get_temp_dir().'/fcup-translations-'.bin2hex(random_bytes(8));
        $app->useLangPath($this->languageDirectory);
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->languageDirectory)) {
                (new Filesystem)->deleteDirectory($this->languageDirectory);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_translations_tag_publishes_every_shipped_locale_to_the_application_language_path(): void
    {
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'filament-complete-user-profile-translations',
            '--no-interaction' => true,
        ]);

        self::assertSame(0, $exitCode);

        foreach (['en', 'pt', 'es', 'fr'] as $locale) {
            $publishedFile = lang_path("vendor/filament-complete-user-profile/{$locale}/profile.php");

            self::assertFileExists($publishedFile);
            self::assertFileEquals(dirname(__DIR__, 2)."/resources/lang/{$locale}/profile.php", $publishedFile);
        }
    }

    public function test_republishing_preserves_application_overrides_and_unmodified_keys_use_package_translations(): void
    {
        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'filament-complete-user-profile-translations',
            '--no-interaction' => true,
        ]);

        self::assertSame(0, $exitCode);

        $publishedFile = lang_path('vendor/filament-complete-user-profile/en/profile.php');
        self::assertFileExists($publishedFile);

        $customTranslations = "<?php\nreturn ['page' => ['heading' => 'Your workspace account']];\n";
        (new Filesystem)->put($publishedFile, $customTranslations);

        $exitCode = Artisan::call('vendor:publish', [
            '--tag' => 'filament-complete-user-profile-translations',
            '--no-interaction' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame($customTranslations, (new Filesystem)->get($publishedFile));
        self::assertSame('Your workspace account', __('filament-complete-user-profile::profile.page.heading', [], 'en'));
        self::assertSame('Manage your profile, security, and access.', __('filament-complete-user-profile::profile.page.subheading', [], 'en'));
    }
}
