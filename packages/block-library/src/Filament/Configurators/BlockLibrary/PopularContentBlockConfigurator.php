<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\Configurators\BlockLibrary;

use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\BlockLibrary\Filament\Components\Forms\ActionsRepeater;
use Capell\BlockLibrary\Filament\Components\Forms\Content\DetailsSchema;
use Capell\BlockLibrary\Filament\Components\Forms\Content\SettingsSchema;
use Capell\BlockLibrary\Filament\Components\Forms\Content\TranslationsRepeater;
use Capell\BlockLibrary\Filament\Components\Forms\CustomColorInput;
use Filament\FormBuilder\Components\Repeater;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\FormBuilder\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

abstract class PopularContentBlockConfigurator extends DefaultContentBlockConfigurator
{
    abstract protected function blockKey(): string;

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
        return match ($this->blockKey()) {
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
            $this->itemsRepeater('items', __('capell-block-library::form.panels'), [
                TextInput::make('heading')
                    ->label(__('capell-block-library::form.heading'))
                    ->required()
                    ->maxLength(160),
                ContentEditor::make('content')
                    ->label(__('capell-admin::generic.content')),
            ]),
            Toggle::make('first_open')
                ->label(__('capell-block-library::form.first_open'))
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
                ->label(__('capell-block-library::form.alignment'))
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
            $this->itemsRepeater('columns', __('capell-block-library::form.columns'), [
                TextInput::make('heading')
                    ->label(__('capell-block-library::form.heading'))
                    ->required()
                    ->maxLength(120),
                Textarea::make('description')
                    ->label(__('capell-block-library::form.description'))
                    ->rows(3),
                Toggle::make('highlighted')
                    ->label(__('capell-block-library::form.highlighted')),
            ])->maxItems(4),
            $this->itemsRepeater('rows', __('capell-block-library::form.rows'), [
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
                TextInput::make('values')
                    ->label(__('capell-block-library::form.values'))
                    ->helperText(__('capell-block-library::generic.comparison_values_helper')),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function counterFields(): array
    {
        return [
            $this->itemsRepeater('counters', __('capell-block-library::form.counters'), [
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.icon')),
                TextInput::make('value')
                    ->label(__('capell-block-library::form.value'))
                    ->required()
                    ->maxLength(40),
                TextInput::make('prefix')
                    ->label(__('capell-block-library::form.prefix'))
                    ->maxLength(20),
                TextInput::make('suffix')
                    ->label(__('capell-block-library::form.suffix'))
                    ->maxLength(20),
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required()
                    ->maxLength(120),
                Textarea::make('description')
                    ->label(__('capell-block-library::form.description'))
                    ->rows(2),
            ]),
            Toggle::make('animate')
                ->label(__('capell-block-library::form.animate'))
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
                ->label(__('capell-block-library::form.style'))
                ->options([
                    'line' => __('capell-block-library::generic.divider_line'),
                    'space' => __('capell-block-library::generic.divider_space'),
                    'dots' => __('capell-block-library::generic.divider_dots'),
                ])
                ->default('line'),
            Select::make('spacing')
                ->label(__('capell-block-library::form.spacing'))
                ->options([
                    'sm' => __('capell-block-library::generic.spacing_sm'),
                    'md' => __('capell-block-library::generic.spacing_md'),
                    'lg' => __('capell-block-library::generic.spacing_lg'),
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
            $this->itemsRepeater('questions', __('capell-block-library::form.questions'), [
                TextInput::make('question')
                    ->label(__('capell-block-library::form.question'))
                    ->required()
                    ->maxLength(180),
                ContentEditor::make('answer')
                    ->label(__('capell-block-library::form.answer')),
            ]),
            Toggle::make('first_open')
                ->label(__('capell-block-library::form.first_open'))
                ->default(false),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function featuresFields(): array
    {
        return [
            $this->itemsRepeater('features', __('capell-block-library::form.features'), [
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.icon')),
                TextInput::make('heading')
                    ->label(__('capell-block-library::form.heading'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('capell-block-library::form.description'))
                    ->rows(3),
                TextInput::make('url')
                    ->label(__('capell-admin::form.url')),
            ]),
            Select::make('columns')
                ->label(__('capell-block-library::form.columns'))
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
            $this->itemsRepeater('logos', __('capell-block-library::form.logos'), [
                MediaLibraryFileUpload::make('image')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('capell-admin::form.name'))
                    ->required(),
                TextInput::make('url')
                    ->label(__('capell-admin::form.url')),
            ]),
            Select::make('columns')
                ->label(__('capell-block-library::form.columns'))
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
            $this->itemsRepeater('plans', __('capell-block-library::form.plans'), [
                TextInput::make('name')
                    ->label(__('capell-admin::form.name'))
                    ->required(),
                TextInput::make('price')
                    ->label(__('capell-block-library::form.price'))
                    ->required(),
                TextInput::make('period')
                    ->label(__('capell-block-library::form.period')),
                Textarea::make('description')
                    ->label(__('capell-block-library::form.description'))
                    ->rows(2),
                Textarea::make('features')
                    ->label(__('capell-block-library::form.features'))
                    ->helperText(__('capell-block-library::generic.line_separated_helper'))
                    ->rows(5),
                TextInput::make('action_label')
                    ->label(__('capell-block-library::form.action_label')),
                TextInput::make('action_url')
                    ->label(__('capell-block-library::form.action_url')),
                Toggle::make('highlighted')
                    ->label(__('capell-block-library::form.highlighted')),
            ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function statsFields(): array
    {
        return [
            $this->itemsRepeater('stats', __('capell-block-library::form.stats'), [
                TextInput::make('value')
                    ->label(__('capell-block-library::form.value'))
                    ->required(),
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('capell-block-library::form.description'))
                    ->rows(2),
            ]),
            Select::make('columns')
                ->label(__('capell-block-library::form.columns'))
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
                ->label(__('capell-block-library::form.caption'))
                ->columnSpanFull(),
            $this->itemsRepeater('headers', __('capell-block-library::form.headers'), [
                TextInput::make('label')
                    ->label(__('capell-admin::form.label'))
                    ->required(),
            ]),
            $this->itemsRepeater('rows', __('capell-block-library::form.rows'), [
                TextInput::make('cells')
                    ->label(__('capell-block-library::form.cells'))
                    ->helperText(__('capell-block-library::generic.table_cells_helper'))
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
            $this->itemsRepeater('tabs', __('capell-block-library::form.tabs'), [
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
            $this->itemsRepeater('members', __('capell-block-library::form.members'), [
                MediaLibraryFileUpload::make('image')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label(__('capell-admin::form.name'))
                    ->required(),
                TextInput::make('role')
                    ->label(__('capell-block-library::form.role')),
                Textarea::make('bio')
                    ->label(__('capell-block-library::form.bio'))
                    ->rows(3),
                TextInput::make('url')
                    ->label(__('capell-admin::form.url')),
            ]),
            Select::make('columns')
                ->label(__('capell-block-library::form.columns'))
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
            $this->itemsRepeater('milestones', __('capell-block-library::form.milestones'), [
                TextInput::make('date')
                    ->label(__('capell-block-library::form.date'))
                    ->required(),
                TextInput::make('heading')
                    ->label(__('capell-block-library::form.heading'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('capell-block-library::form.description'))
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
            ->addActionLabel(__('capell-block-library::button.add_item'));
    }

    /**
     * @return array<string, string>
     */
    private function alignmentOptions(): array
    {
        return [
            'start' => __('capell-block-library::generic.align_start'),
            'center' => __('capell-block-library::generic.align_center'),
            'end' => __('capell-block-library::generic.align_end'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function columnOptions(): array
    {
        return [
            '2' => __('capell-block-library::generic.two_columns'),
            '3' => __('capell-block-library::generic.three_columns'),
            '4' => __('capell-block-library::generic.four_columns'),
        ];
    }
}
