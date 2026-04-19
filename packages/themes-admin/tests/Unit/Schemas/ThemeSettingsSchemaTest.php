<?php

declare(strict_types=1);

namespace Capell\Themes\Admin\Tests\Unit\Schemas;

use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Orchestra\Testbench\TestCase;

class ThemeSettingsSchemaTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Filament\FilamentServiceProvider::class,
            \Filament\Forms\FormsServiceProvider::class,
            \Filament\Schemas\SchemasServiceProvider::class,
            \Filament\Support\SupportServiceProvider::class,
        ];
    }

    public function test_theme_settings_schema_returns_tabs(): void
    {
        $schema = ThemeSettingsSchema::make();

        $this->assertInstanceOf(Tabs::class, $schema);
        $this->assertNotEmpty($schema->getChildComponents());
    }

    public function test_schema_contains_active_theme_select(): void
    {
        $schema = ThemeSettingsSchema::make();
        $components = $this->flattenComponents($schema);

        $activeTheme = array_filter(
            $components,
            fn ($c) => $c instanceof Select && $c->getName() === 'active_theme',
        );

        $this->assertCount(1, $activeTheme);
    }

    public function test_schema_includes_color_pickers(): void
    {
        $schema = ThemeSettingsSchema::make();
        $components = $this->flattenComponents($schema);

        $colorPickers = array_filter($components, fn ($c) => $c instanceof ColorPicker);

        $this->assertGreaterThanOrEqual(2, count($colorPickers));
    }

    private function flattenComponents(object $component): array
    {
        $result = [$component];

        if (method_exists($component, 'getChildComponents')) {
            foreach ($component->getChildComponents() as $child) {
                $result = array_merge($result, $this->flattenComponents($child));
            }
        }

        return $result;
    }
}
