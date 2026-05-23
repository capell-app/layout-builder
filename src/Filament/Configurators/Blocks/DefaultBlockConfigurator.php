<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Blocks;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\ImageSourcePicker;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\ActionsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\ComponentSection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\DisplaySection;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockDisplayTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\Tab\BlockSettingsTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Block\TranslationsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class DefaultBlockConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Block;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Block->value);
    }

    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionSchema($configurator),
            'editOption' => $this->getEditOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($configurator)
                        ->contained(),
                    ...$this->getExtraSchema($configurator),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['@md' => 2])
                        ->schema([
                            ...SettingsSchema::make($configurator),
                            ImageSourcePicker::make('image')
                                ->sourceStatePath('meta.image_source')
                                ->imageSourcePolicy(blueprintSources: $this->blueprintImageSourcePolicy($configurator, 'image')),
                        ]),
                ]),
        ];
    }

    protected function getEditOptionSchema(Schema $configurator): array
    {
        return [
            TranslationsRepeater::make($configurator),
            ...$this->getExtraSchema($configurator, withSettingsTab: true),
        ];
    }

    protected function getCreateOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator),
            ...$this->getExtraSchema($configurator),
        ];
    }

    protected function getExtraSchema(Schema $configurator, bool $withSettingsTab = false): array
    {
        return [
            $this->getTabs($configurator, $withSettingsTab),
        ];
    }

    protected function getTabs(Schema $configurator, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                $this->detailsTab(),
                $this->displayTab($configurator),
                ...$withSettingsTab ? [$this->settingsTab($configurator)] : [],
                BlockAdminTab::make(),
            ]);
    }

    protected function displayTab(Schema $configurator): Tab
    {
        return BlockDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
                Checkbox::make('reverse_order')
                    ->label(__('capell-layout-builder::form.reverse_order'))
                    ->whenTruthy('image'),
            ]),
            ComponentSection::make(),
        ]);
    }

    protected function detailsTab(): Tab
    {
        return Tab::make('details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                ActionsRepeater::make('actions'),
            ]);
    }

    protected function settingsTab(Schema $configurator): Tab
    {
        return BlockSettingsTab::make($configurator);
    }

    protected function blueprintImageSourcePolicy(Schema $schema, string $field): string|array|null
    {
        $record = $schema->getRecord();
        $blueprint = null;

        if ($record instanceof Model && $record->relationLoaded('blueprint')) {
            $relation = $record->getRelation('blueprint');
            $blueprint = $relation instanceof Blueprint ? $relation : null;
        }

        $policy = data_get($blueprint?->admin, 'image_source_policy.' . $field);

        return is_string($policy) || is_array($policy) ? $policy : null;
    }
}
