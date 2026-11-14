<?php

namespace Mortalkiller\FilamentCompleteUserProfile\Tests\Unit;

use PHPUnit\Framework\TestCase;

class StylesheetTest extends TestCase
{
    private function stylesheet(): string
    {
        return (string) file_get_contents(__DIR__.'/../../resources/css/profile.css');
    }

    public function test_the_styles_sit_in_their_own_layer_after_filaments_components_so_a_panel_theme_can_restyle_them(): void
    {
        $css = $this->stylesheet();

        // The layer order comes before any rule, with the package layer between Filament's components and utilities.
        $order = strpos($css, '@layer theme, base, components, fcup-profile, utilities;');
        $this->assertNotFalse($order);
        $this->assertLessThan(strpos($css, '{'), $order);
        $this->assertSame(1, substr_count($css, '@layer fcup-profile {'));
        $this->assertStringNotContainsString('@layer components {', $css);
    }

    public function test_every_colour_comes_from_the_panel_palette(): void
    {
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $this->stylesheet());
    }
}
