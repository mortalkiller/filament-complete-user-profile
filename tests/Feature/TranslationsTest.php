<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Illuminate\Support\Arr;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class TranslationsTest extends TestCase
{
    public function test_package_ships_complete_english_portuguese_spanish_and_french_translations(): void
    {
        $langPath = dirname(__DIR__, 2).'/resources/lang';
        $english = require $langPath.'/en/profile.php';
        $englishKeys = array_keys(Arr::dot($english));

        foreach (['pt', 'es', 'fr'] as $locale) {
            $file = $langPath."/{$locale}/profile.php";

            if (! file_exists($file)) {
                self::fail("Missing [{$locale}] package translation file.");
            }

            $translations = require $file;

            self::assertSame(
                $englishKeys,
                array_keys(Arr::dot($translations)),
                "Translation [{$locale}] must contain the same keys as English.",
            );
        }
    }

    public function test_page_copy_is_translated_for_each_shipped_locale(): void
    {
        $expected = [
            'en' => 'My account',
            'pt' => 'A minha conta',
            'es' => 'Mi cuenta',
            'fr' => 'Mon compte',
        ];

        foreach ($expected as $locale => $heading) {
            app()->setLocale($locale);

            self::assertSame(
                $heading,
                __('filament-complete-user-profile::profile.page.heading'),
            );
        }
    }
}
