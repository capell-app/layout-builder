<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Configurators\Sections;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\ContentSections\Filament\Components\Forms\ActionsRepeater;
use Capell\ContentSections\Filament\Components\Forms\Content\DetailsSchema;
use Capell\ContentSections\Filament\Components\Forms\Content\SettingsSchema;
use Capell\ContentSections\Filament\Components\Forms\Content\TranslationsRepeater;
use Capell\ContentSections\Filament\Components\Forms\CustomColorInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

abstract class PopularSectionConfigurator extends DefaultSectionConfigurator
{
    abstract protected function sectionKey(): string;

    protected function getOptionFormSchema(Schema $configurator): array
    {
        return [
            ...DetailsSchema::make($configurator),
            Tabs::make()
                ->columnSpanFull()
                ->tabs($this->tabs($configurator)),
            PublishSection::make(),
        ];
    }

    protected function getFormSchema(Schema $configurator): array
    {
        return [
            Section::make()
                ->hiddenOn('edit')
                ->columnSpanFull()
                ->columns()
                ->schema(DetailsSchema::make($configurator))
                ->contained(fn (string $operation): bool => $operation === 'create'),
            FixedWidthSidebar::make()
                ->mainSchema([
                    Tabs::make()
                        ->tabs($this->tabs($configurator)),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->gridContainer()
                        ->columns(['default' => 1, '@lg' => 2])
                        ->schema([
                            ...($configurator->getOperation() !== 'create' ? DetailsSchema::make($configurator) : []),
                            ...SettingsSchema::make($configurator),
                        ]),
                    PublishSection::make(),
                ]),
        ];
    }

    /**
     * @return array<int, Tab>
     */
    protected function tabs(Schema $configurator): array
    {
        return [
            Tab::make(__('capell-admin::tab.content'))
                ->icon(Heroicon::Language)
                ->schema([
                    TranslationsRepeater::make(
                        configurator: $configurator,
                        components: $this->translationFields(),
                        hasContent: $this->hasMainContentField(),
                    )->hiddenLabel(),
                ]),
            Tab::make(__('capell-admin::generic.settings'))
                ->statePath('meta')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->columns()
                ->schema($this->metaFields($configurator)),
        ];
    }

    protected function hasMainContentField(): bool
    {
        return true;
    }

    /**
     * @return array<int, mixed>
     */
    protected function translationFields(): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected function metaFields(Schema $configurator): array
    {
        return match ($this->sectionKey()) {
            'accordion' => $this->accordionFields(),
            'call_to_action' => $this->callToActionFields(),
            'comparison' => $this->comparisonFields(),
            'counter' => $this->counterFields(),
            'divider' => $this->dividerFields(),
            'faq' => $this->faqFields(),
            'features' => $this->featuresFields(),
            'logos' => $this->logosFields(),
            'pricing' => $this->pricingFields(),
            'stats' => $this->statsFields(),
            'table' => $this->tableFields(),
            'tabs' => $this->tabsFields(),
            'team' => $this->teamFields(),
            'timeline' => $this->timelineFields(),
            default => [],
        };
    }

    /**
     * @return array<int, mixed>
     */
    private function accordionFields(): array
    {
        return [
            $this->itemsRepeater('items', __('capell-content-sections::form.panels'), [
                TextInput::make('heading')
                    ->label(__('capell-content-sections::form.heading'))
                    ->required()
                    ->maxLength(160),
                ContentEditor::make('content')
                    ->label(__('capell-admin::generic.content')),
            ]),
            Toggle::make('first_open')
                ->label(__('capell-content-sections::form.first_open'))
                ->default(false),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function callToActionFields(): array
    {
        return [
            MediaLibraryFileUpload::make('image')
                ->columnSpanFull(),
            CustomColorInput::make('color', __('capell-admin::form.color')),
            Select::make('alignment')
                ->label(__('capell-content-sections::form.alignment'))
                ->options($this->alignmentOptions())
                ->default('center'),
            ActionsRepeater::make('actions'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function comparisonFields(): array
    {
        return [
            $this->itemsRepeater('columns', __('capell-content-sections::form.columns'), [
                TextInput::make('heading')
                    ->label(__('capell-content-sections::form.heading'))
                    ->required()
                    ->maxLength(120),
                Textarea::make('description')
                    ->label(__('capell-content-sections::form.description'))
                    ->rows(3),
                Toggle::make('highlighted')
                    ->label(__('capell-content-sections::form.highlighted')),
            ])->maxItems(4),
            $this->itemsRepeater('rows', __('capell-content-sections::form.rows'), [
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
                TextInput::make('values')
                    ->label(__('capell-content-sections::form.values'))
                    ->helperText(__('capell-content-sections::generic.comparison_values_helper')),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function counterFields(): array
    {
        return [
            $this->itemsRepeater('counters', __('capell-content-sections::form.counters'), [
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.icon')),
                TextInput::make('value')
                    ->label(__('capell-content-sections::form.value'))
                    ->required()
                    ->maxLength(40),
                TextInput::make('prefix')
                    ->label(__('capell-content-sections::form.prefix'))
                    ->maxLength(20),
                TextInput::make('suffix')
                    ->label(__('capell-content-sections::form.suffix'))
                    ->maxLength(20),
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required()
                    ->maxLength(120),
                Textarea::make('description')
                    ->label(__('capell-content-sections::form.description'))
                    ->rows(2),
            ]),
            Toggle::make('animate')
                ->label(__('capell-content-sections::form.animate'))
                ->default(true),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function dividerFields(): array
    {
        return [
            Select::make('style')
                ->label(__('capell-content-sections::form.style'))
                ->options([
                    'line' => __('capell-content-sections::generic.divider_line'),
                    'space' => __('capell-content-sections::generic.divider_space'),
                    'dots' => __('capell-content-sections::generic.divider_dots'),
                ])
                ->default('line'),
            Select::make('spacing')
                ->label(__('capell-content-sections::form.spacing'))
                ->options([
                    'sm' => __('capell-content-sections::generic.spacing_sm'),
                    'md' => __('capell-content-sections::generic.spacing_md'),
                    'lg' => __('capell-content-sections::generic.spacing_lg'),
                ])
                ->default('md'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function faqFields(): array
    {
        return [
            $this->itemsRepeater('questions', __('capell-content-sections::form.questions'), [
                TextInput::make('question')
                    ->label(__('capell-content-sections::form.question'))
                    ->required()
                    ->maxLength(180),
                ContentEditor::make('answer')
                    ->label(__('capell-content-sections::form.answer')),
            ]),
            Toggle::make('first_open')
                ->label(__('capell-content-sections::form.first_open'))
                ->default(false),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function featuresFields(): array
    {
        return [
            $this->itemsRepeater('features', __('capell-content-sections::form.features'), [
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.icon')),
                TextInput::make('heading')
                    ->label(__('capell-content-sections::form.heading'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('capell-content-sections::form.description'))
                    ->rows(3),
                TextInput::make('url')
                    ->label(__('capell-admin::form.url')),
            ]),
            Select::make('columns')
                ->label(__('capell-content-sections::form.columns'))
                ->options($this->columnOptions())
                ->default('3'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function logosFields(): array
    {
        return [
            $this->itemsRepeater('logos', __('capell-content-sections::form.logos'), [
                MediaLibraryFileUpload::make('image')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('capell-admin::form.name'))
                    ->required(),
                TextInput::make('url')
                    ->label(__('capell-admin::form.url')),
            ]),
            Select::make('columns')
                ->label(__('capell-content-sections::form.columns'))
                ->options($this->columnOptions())
                ->default('4'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function pricingFields(): array
    {
        return [
            $this->itemsRepeater('plans', __('capell-content-sections::form.plans'), [
                TextInput::make('name')
                    ->label(__('capell-admin::form.name'))
                    ->required(),
                TextInput::make('price')
                    ->label(__('capell-content-sections::form.price'))
                    ->required(),
                TextInput::make('period')
                    ->label(__('capell-content-sections::form.period')),
                Textarea::make('description')
                    ->label(__('capell-content-sections::form.description'))
                    ->rows(2),
                Textarea::make('features')
                    ->label(__('capell-content-sections::form.features'))
                    ->helperText(__('capell-content-sections::generic.line_separated_helper'))
                    ->rows(5),
                TextInput::make('action_label')
                    ->label(__('capell-content-sections::form.action_label')),
                TextInput::make('action_url')
                    ->label(__('capell-content-sections::form.action_url')),
                Toggle::make('highlighted')
                    ->label(__('capell-content-sections::form.highlighted')),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function statsFields(): array
    {
        return [
            $this->itemsRepeater('stats', __('capell-content-sections::form.stats'), [
                TextInput::make('value')
                    ->label(__('capell-content-sections::form.value'))
                    ->required(),
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('capell-content-sections::form.description'))
                    ->rows(2),
            ]),
            Select::make('columns')
                ->label(__('capell-content-sections::form.columns'))
                ->options($this->columnOptions())
                ->default('4'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function tableFields(): array
    {
        return [
            TextInput::make('caption')
                ->label(__('capell-content-sections::form.caption'))
                ->columnSpanFull(),
            $this->itemsRepeater('headers', __('capell-content-sections::form.headers'), [
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
            ]),
            $this->itemsRepeater('rows', __('capell-content-sections::form.rows'), [
                TextInput::make('cells')
                    ->label(__('capell-content-sections::form.cells'))
                    ->helperText(__('capell-content-sections::generic.table_cells_helper'))
                    ->required(),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function tabsFields(): array
    {
        return [
            $this->itemsRepeater('tabs', __('capell-content-sections::form.tabs'), [
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.icon')),
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
                ContentEditor::make('content')
                    ->label(__('capell-admin::generic.content')),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function teamFields(): array
    {
        return [
            $this->itemsRepeater('members', __('capell-content-sections::form.members'), [
                MediaLibraryFileUpload::make('image')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('capell-admin::form.name'))
                    ->required(),
                TextInput::make('role')
                    ->label(__('capell-content-sections::form.role')),
                Textarea::make('bio')
                    ->label(__('capell-content-sections::form.bio'))
                    ->rows(3),
                TextInput::make('url')
                    ->label(__('capell-admin::form.url')),
            ]),
            Select::make('columns')
                ->label(__('capell-content-sections::form.columns'))
                ->options($this->columnOptions())
                ->default('3'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function timelineFields(): array
    {
        return [
            $this->itemsRepeater('milestones', __('capell-content-sections::form.milestones'), [
                TextInput::make('date')
                    ->label(__('capell-content-sections::form.date'))
                    ->required(),
                TextInput::make('heading')
                    ->label(__('capell-content-sections::form.heading'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('capell-content-sections::form.description'))
                    ->rows(3),
            ]),
        ];
    }

    /**
     * @param  array<int, mixed>  $schema
     */
    private function itemsRepeater(string $name, string $label, array $schema): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema($schema)
            ->columnSpanFull()
            ->cloneable()
            ->collapsible()
            ->orderColumn()
            ->defaultItems(0)
            ->itemLabel(fn (array $state): ?string => $state['heading'] ?? $state['label'] ?? $state['name'] ?? $state['question'] ?? null)
            ->addActionLabel(__('capell-content-sections::button.add_item'));
    }

    /**
     * @return array<string, string>
     */
    private function alignmentOptions(): array
    {
        return [
            'start' => __('capell-content-sections::generic.align_start'),
            'center' => __('capell-content-sections::generic.align_center'),
            'end' => __('capell-content-sections::generic.align_end'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function columnOptions(): array
    {
        return [
            '2' => __('capell-content-sections::generic.two_columns'),
            '3' => __('capell-content-sections::generic.three_columns'),
            '4' => __('capell-content-sections::generic.four_columns'),
        ];
    }
}
