<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;

class LocaleRegistryTest extends TestCase
{
    public function test_common_locale_codes_resolve_to_native_language_names(): void
    {
        $name = $this->localeNameMethod();

        self::assertSame('Português', $name->invoke(null, 'pt'));
        self::assertSame('English', $name->invoke(null, 'en'));
        self::assertSame('Español', $name->invoke(null, 'es'));
        self::assertSame('Français', $name->invoke(null, 'fr'));
        self::assertSame('Deutsch', $name->invoke(null, 'de'));
        self::assertSame('Italiano', $name->invoke(null, 'it'));
    }

    public function test_regional_locale_codes_resolve_to_specific_language_names(): void
    {
        $name = $this->localeNameMethod();

        self::assertSame('Português (Portugal)', $name->invoke(null, 'pt_PT'));
        self::assertSame('Português (Brasil)', $name->invoke(null, 'pt_BR'));
        self::assertSame('English (United Kingdom)', $name->invoke(null, 'en_GB'));
        self::assertSame('English (United States)', $name->invoke(null, 'en_US'));
    }

    public function test_unknown_locale_codes_are_preserved_instead_of_inventing_a_name(): void
    {
        self::assertSame('xx_YY', $this->localeNameMethod()->invoke(null, 'xx_YY'));
    }

    private function localeNameMethod(): ReflectionMethod
    {
        $registryClass = 'Mortalkiller\\FilamentCompleteUserProfile\\Support\\LocaleRegistry';

        if (! class_exists($registryClass)) {
            self::fail('LocaleRegistry is missing.');
        }

        $reflection = new ReflectionClass($registryClass); // @phpstan-ignore argument.type

        if (! $reflection->hasMethod('name')) {
            self::fail('LocaleRegistry::name() is missing.');
        }

        return $reflection->getMethod('name');
    }
}
