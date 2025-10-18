<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\AssetsRepeater;
use Capell\Layout\Filament\Components\Forms\BackgroundSchema;
use Capell\Layout\Filament\Components\Forms\CarouselSettingsSchema;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class HeroWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static string $schemaType = SchemaTypeEnum::Widget->value;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Widget->value);
    }

    public function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return [
            ...match ($operation) {
                'editOption', 'createOption', 'replicate' => $this->getOptionSchema($schema),
                default => $this->getFormSchema($schema),
            },
        ];
    }

    protected function getOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            self::getAssetsComponent($schema),
            ...$this->getMetaSchema(),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    self::getAssetsComponent($schema),
                ])
                ->sidebarSchema(
                    WidgetSettingsSchema::make($schema),
                    contained: true
                ),
            $this->getTabs($schema),
        ];
    }

    protected function getTabs(Schema $schema): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                Tab::make(__('capell-admin::tab.content'))
                    ->icon('heroicon-o-language')
                    ->schema([
                        WidgetTranslationsRepeater::make($schema)
                            ->contained(false),
                    ]),
                WidgetDisplayTab::make([
                    Grid::make()
                        ->schema([
                            ...$this->getMetaSchema(),
                            WidgetComponentFilesSection::make()
                                ->statePath('meta'),
                        ]),
                ]),
                WidgetAdminTab::make(),
                $this->getSettingsTab($schema),
            ]);
    }

    protected function getMetaSchema(): array
    {
        return [
            Grid::make(['default' => 2, 'xl' => 3])
                ->schema([
                    Group::make()
                        ->statePath('meta')
                        ->schema([
                            ColorSchemeComponent::make('color_scheme'),
                            Select::make('height')
                                ->label(__('capell-admin::form.height'))
                                ->options([
                                    'small' => __('capell-admin::generic.small'),
                                    'medium' => __('capell-admin::generic.medium'),
                                    'large' => __('capell-admin::generic.large'),
                                    'full' => __('capell-admin::generic.full'),
                                ])
                                ->default('medium')
                                ->required(),
                        ]),
                    ...BackgroundSchema::make(),
                ]),

            Fieldset::make(__('capell-admin::generic.carousel_options'))
                ->columns(['default' => 2, 'xl' => 3])
                ->statePath('meta')
                ->schema(CarouselSettingsSchema::make()),
        ];
    }

    protected function getAssetsComponent(Schema $schema): Component
    {
        return AssetsRepeater::make('assets')
            ->compact()
            ->hiddenLabel()
            ->hint(__('capell-admin::generic.widget_assets_repeater_hint'));
    }

    protected function getSettingsTab(Schema $schema): Tab
    {
        return WidgetSettingsTab::make($schema);
    }
}
