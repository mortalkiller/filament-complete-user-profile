<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Mortalkiller\FilamentCompleteUserProfile\Support\LocaleRegistry;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class LocaleRegistryTest extends TestCase
{
    public function test_common_locale_codes_resolve_to_native_language_names(): void
    {
        self::assertSame('Português', LocaleRegistry::name('pt'));
        self::assertSame('English', LocaleRegistry::name('en'));
        self::assertSame('Español', LocaleRegistry::name('es'));
        self::assertSame('Français', LocaleRegistry::name('fr'));
        self::assertSame('Deutsch', LocaleRegistry::name('de'));
        self::assertSame('Italiano', LocaleRegistry::name('it'));
    }

    public function test_regional_locale_codes_resolve_to_specific_language_names(): void
    {
        self::assertSame('Português (Portugal)', LocaleRegistry::name('pt_PT'));
        self::assertSame('Português (Brasil)', LocaleRegistry::name('pt_BR'));
        self::assertSame('English (United Kingdom)', LocaleRegistry::name('en_GB'));
        self::assertSame('English (United States)', LocaleRegistry::name('en_US'));
    }

    public function test_unknown_locale_codes_are_preserved_instead_of_inventing_a_name(): void
    {
        self::assertSame('xx_YY', LocaleRegistry::name('xx_YY'));
    }
}
