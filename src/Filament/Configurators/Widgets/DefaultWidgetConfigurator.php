<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Configurators\Widgets;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\ImageSourcePicker;
use Capell\Admin\Filament\Concerns\HasConfigurator;
use Capell\Core\Enums\ImageSourceType;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Contracts\Extenders\WidgetSchemaExtender;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\SchemaExtenderEnum;
use Capell\LayoutBuilder\Filament\Components\Forms\ActionsRepeater;
use Capell\LayoutBuilder\Filament\Components\Forms\ColorSchemeComponent;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetSettingsTab;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class DefaultWidgetConfigurator implements ConfiguratorInterface
{
    use HasConfigurator;

    protected static ConfiguratorTypeEnumInterface $configuratorType = ConfiguratorTypeEnum::Widget;

    public static function getKey(): string
    {
        return preg_replace('/WidgetConfigurator$/', '', class_basename(static::class)) ?? class_basename(static::class);
    }

    /**
     * @return iterable<int, mixed>
     */
    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Widget->value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function make(Schema $configurator): array
    {
        return match ($configurator->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionSchema($configurator),
            'editOption' => $this->getEditOptionSchema($configurator),
            default => $this->getFormSchema($configurator),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
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

    /**
     * @return array<array-key, mixed>
     */
    protected function getEditOptionSchema(Schema $configurator): array
    {
        return [
            TranslationsRepeater::make($configurator),
            ...$this->getExtraSchema($configurator, withSettingsTab: true),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function getCreateOptionSchema(Schema $configurator): array
    {
        return [
            CreateDetailsSchema::make($configurator),
            TranslationsRepeater::make($configurator),
            ...$this->getExtraSchema($configurator),
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
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
                ...$this->displayTabs($configurator),
                ...$withSettingsTab ? [$this->settingsTab($configurator)] : [],
                WidgetAdminTab::make(),
            ]);
    }

    /**
     * @return array<int, Tab>
     */
    protected function displayTabs(Schema $configurator): array
    {
        return WidgetPresentationTabs::make(
            styleFields: [
                ...$this->extendDisplayComponents($configurator, [
                    ColorSchemeComponent::make('color'),
                    Checkbox::make('reverse_order')
                        ->label(__('capell-layout-builder::form.reverse_order'))
                        ->whenTruthy('image'),
                ]),
            ],
        );
    }

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, mixed>
     */
    protected function extendDisplayComponents(Schema $configurator, array $components): array
    {
        foreach (static::getExtenders() as $extender) {
            if ($extender instanceof WidgetSchemaExtender) {
                $components = $extender->extendDisplayComponents($configurator, $components);
            }
        }

        return $components;
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
        return WidgetSettingsTab::make($configurator, [
            ImageSourcePicker::make('image')
                ->sourceStatePath('meta.image_source')
                ->imageSourcePolicy(blueprintSources: $this->blueprintImageSourcePolicy($configurator, 'image')),
        ]);
    }

    /**
     * @return list<ImageSourceType|string>|string|null
     */
    protected function blueprintImageSourcePolicy(Schema $schema, string $field): string|array|null
    {
        $record = $schema->getRecord();
        $blueprint = null;

        if ($record instanceof Model && $record->relationLoaded('blueprint')) {
            $relation = $record->getRelation('blueprint');
            $blueprint = $relation instanceof Blueprint ? $relation : null;
        }

        $policy = data_get($blueprint?->admin, 'image_source_policy.' . $field);

        if (is_string($policy)) {
            return $policy;
        }

        if (! is_array($policy)) {
            return null;
        }

        return array_values(array_filter(
            $policy,
            static fn (mixed $source): bool => is_string($source) || $source instanceof ImageSourceType,
        ));
    }
}
