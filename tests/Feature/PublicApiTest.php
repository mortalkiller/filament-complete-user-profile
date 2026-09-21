<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Feature;

use Mortalkiller\FilamentCompleteUserProfile\AccountSection;
use Mortalkiller\FilamentCompleteUserProfile\CompleteUserProfilePlugin;
use Mortalkiller\FilamentCompleteUserProfile\Features\Security;
use Mortalkiller\FilamentCompleteUserProfile\Tests\TestCase;
use ReflectionClass;

class PublicApiTest extends TestCase
{
    public function test_plugin_exposes_only_the_canonical_custom_section_registration_method(): void
    {
        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        self::assertTrue($reflection->hasMethod('section'));
        self::assertFalse($reflection->hasMethod('sections'));
    }

    public function test_account_section_exposes_the_canonical_fluent_configuration_api(): void
    {
        $reflection = new ReflectionClass(AccountSection::class);

        foreach (['make', 'label', 'description', 'sort', 'visible', 'schema'] as $method) {
            self::assertTrue($reflection->hasMethod($method), "[{$method}] should be part of the AccountSection API.");
        }

        foreach (['badge', 'group', 'view', 'saveUsing', 'afterSave'] as $method) {
            self::assertFalse($reflection->hasMethod($method), "[{$method}] should not be part of the AccountSection API.");
        }
    }

    public function test_redundant_feature_configurators_are_removed(): void
    {
        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        foreach (['overviewWith', 'profileWith', 'securityWith', 'sessionsWith', 'apiTokensWith'] as $method) {
            self::assertFalse($reflection->hasMethod($method), "[{$method}] should not be part of the public API.");
        }
    }

    public function test_security_options_are_only_configured_inside_the_security_feature(): void
    {
        $reflection = new ReflectionClass(CompleteUserProfilePlugin::class);

        self::assertFalse($reflection->hasMethod('multiFactorAuthentication'));
        self::assertFalse($reflection->hasMethod('emailAuthentication'));
    }

    public function test_security_uses_app_authentication_vocabulary(): void
    {
        $reflection = new ReflectionClass(Security::class);

        self::assertTrue($reflection->hasMethod('appAuthentication'));
        self::assertTrue($reflection->hasMethod('hasAppAuthentication'));
        self::assertTrue($reflection->hasMethod('getAppAuthenticationRequirementIssue'));
        self::assertFalse($reflection->hasMethod('multiFactorAuthentication'));
        self::assertFalse($reflection->hasMethod('hasMultiFactorAuthentication'));
        self::assertFalse($reflection->hasMethod('getMultiFactorAuthenticationRequirementIssue'));
    }

    public function test_primary_documentation_uses_only_the_canonical_api(): void
    {
        $root = dirname(__DIR__, 2);
        $files = [
            $root.'/README.md',
            $root.'/docs/roadmap.md',
            $root.'/docs/superpowers/specs/2026-09-15-filament-complete-user-profile-design.md',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            self::assertIsString($contents);
            self::assertStringNotContainsString('->navigationLayout(', $contents, $file);
            self::assertStringNotContainsString('->multiFactorAuthentication(', $contents, $file);
            self::assertStringNotContainsString('->profileWith(', $contents, $file);
            self::assertStringNotContainsString('->securityWith(', $contents, $file);
            self::assertStringNotContainsString('->sessionsWith(', $contents, $file);
            self::assertStringNotContainsString('->apiTokensWith(', $contents, $file);
            self::assertStringNotContainsString('->sections(', $contents, $file);
        }

        $readme = file_get_contents($root.'/README.md');

        self::assertIsString($readme);
        self::assertStringContainsString('->appAuthentication()', $readme);
        self::assertStringContainsString('->emailAuthentication()', $readme);
        self::assertStringContainsString('AccountSection::make(', $readme);
        self::assertStringContainsString('does not automatically persist', $readme);
        self::assertStringContainsString('## AccountSection API reference', $readme);

        foreach (['make(string $id)', 'label(string|Closure $label)', 'description(string|Closure|null $description)', 'sort(int $sort)', 'visible(bool|Closure $condition = true)', 'schema(array|Closure $components)', 'getId()', 'getLabel()', 'getDescription()', 'getSort()', 'isVisible()', 'getSchema()'] as $signature) {
            self::assertStringContainsString($signature, $readme, "README should document AccountSection::{$signature}.");
        }

        foreach (['section(AccountSection $section)', 'getSections()', 'getVisibleSections()'] as $signature) {
            self::assertStringContainsString($signature, $readme, "README should document CompleteUserProfilePlugin::{$signature}.");
        }

        self::assertStringContainsString('There is intentionally no `sections()` method', $readme);
    }
}
