<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use Filament\Schemas\Components\Section;
use InvalidArgumentException;
use LogicException;
use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;

class AccountSectionTest extends TestCase
{
    public function test_section_has_predictable_defaults(): void
    {
        $section = AccountSection::make('connected-accounts');

        self::assertSame('connected-accounts', $section->getId());
        self::assertSame('Connected Accounts', $section->getLabel());
        self::assertNull($section->getDescription());
        self::assertSame(100, $section->getSort());
        self::assertTrue($section->isVisible());
        self::assertSame([], $section->getSchema());
    }

    public function test_section_configuration_is_fluent_and_supports_closures(): void
    {
        $component = Section::make('Custom content');
        $section = AccountSection::make('preferences');

        self::assertSame($section, $section->label(fn (): string => 'Preferences'));
        self::assertSame($section, $section->description(fn (): string => 'Manage preferences.'));
        self::assertSame($section, $section->sort(25));
        self::assertSame($section, $section->visible(fn (): bool => true));
        self::assertSame($section, $section->schema(fn (): array => [$component]));

        self::assertSame('Preferences', $section->getLabel());
        self::assertSame('Manage preferences.', $section->getDescription());
        self::assertSame(25, $section->getSort());
        self::assertTrue($section->isVisible());
        self::assertSame([$component], $section->getSchema());
    }

    public function test_visibility_can_be_disabled(): void
    {
        $section = AccountSection::make('preferences')->visible(false);

        self::assertFalse($section->isVisible());
    }

    public function test_schema_accepts_a_component_array(): void
    {
        $component = Section::make('Custom content');
        $section = AccountSection::make('preferences')->schema([$component]);

        self::assertSame([$component], $section->getSchema());
    }

    public function test_section_rejects_invalid_ids(): void
    {
        foreach (['', 'Connected Accounts', 'connected_accounts', '-preferences', 'preferences-'] as $id) {
            try {
                AccountSection::make($id);
                self::fail("Account section ID [{$id}] should be rejected.");
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('lowercase kebab-case', $exception->getMessage());
            }
        }
    }

    public function test_schema_callback_must_return_an_array(): void
    {
        $section = AccountSection::make('preferences')
            ->schema(fn (): string => 'invalid');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Account section schema callback must return an array');

        $section->getSchema();
    }

    public function test_schema_must_contain_only_filament_schema_components(): void
    {
        $section = AccountSection::make('preferences')->schema([
            new \stdClass,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Account section schema must contain only Filament schema components');

        $section->getSchema();
    }

    public function test_sections_no_longer_expose_an_icon_api(): void
    {
        $reflection = new \ReflectionClass(AccountSection::class);

        self::assertFalse($reflection->hasMethod('icon'));
        self::assertFalse($reflection->hasMethod('getIcon'));
    }
}
