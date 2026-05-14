<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\PageSelect;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\LayoutBuilder\Enums\ActionLinkEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ActionsRepeater extends Repeater
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::generic.action'))
            ->statePath('actions')
            ->columnSpanFull()
            ->collapsed(function (?Schema $item): bool {
                $state = $item->getRawState();

                return (isset($state['pageable_id']) && filled($state['pageable_id'])) || (isset($state['url']) && filled($state['url']));
            })
            ->cloneable()
            ->orderColumn()
            ->defaultItems(0)
            ->addActionLabel(__('capell-layout-builder::button.add_action'))
            ->itemLabel(function (array $state, string $key): ?string {
                $type = $this->getItemType($key);

                if (! $type instanceof ActionLinkEnum) {
                    return null;
                }

                $label = $state['label'] ?? null;

                if (filled($label)) {
                    return $label;
                }

                $itemLabel = match ($type) {
                    ActionLinkEnum::Page => (function () use ($state): ?string {
                        $pageableType = $state['pageable_type'] ?? null;
                        $pageableId = $state['pageable_id'] ?? null;

                        if (! is_string($pageableType) || blank($pageableType) || blank($pageableId)) {
                            return null;
                        }

                        $modelClass = Relation::getMorphedModel($pageableType) ?? $pageableType;

                        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                            return null;
                        }

                        $name = $modelClass::query()->whereKey($pageableId)->value('name');

                        return is_string($name) ? $name : null;
                    })(),
                    ActionLinkEnum::Link => $state['url'],
                };

                if (filled($itemLabel)) {
                    return __(
                        'capell-layout-builder::generic.action_type_label',
                        ['type' => $type->getLabel(), 'label' => $itemLabel],
                    );
                }

                return __('capell-admin::generic.action');
            })
            ->schema([
                ToggleButtons::make('type')
                    ->label(__('capell-admin::form.type'))
                    ->required()
                    ->grouped()
                    ->live()
                    ->options(ActionLinkEnum::class)
                    ->afterStateUpdated(function (?ActionLinkEnum $state, Set $set): void {
                        if ($state === ActionLinkEnum::Page) {
                            $set('url', null);
                        }

                        if ($state === ActionLinkEnum::Link) {
                            $set('pageable_id', null);
                        }
                    }),
                Grid::make(['md' => 2, 'lg' => 3])
                    ->visible(fn (Get $get): bool => $get('type') === ActionLinkEnum::Page)
                    ->columnSpanFull()
                    ->schema([
                        PageSelect::make('pageable_id')
                            ->required()
                            ->reactive()
                            ->columnSpan(['lg' => 2]),
                        SiteSelect::make('site_id')
                            ->preload()
                            ->reactive(),
                    ]),

                TextInput::make('url')
                    ->label(__('capell-admin::form.url'))
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => $get('type') === ActionLinkEnum::Link)
                    ->validationAttribute(__('capell-admin::form.url'))
                    ->required()
                    ->lazy(),

                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label')
                            ->label(__('capell-admin::form.label')),
                        IconPicker::make('icon')
                            ->label(__('capell-admin::form.icon')),
                        Select::make('color')
                            ->label(__('capell-admin::form.color'))
                            ->options([
                                'primary' => __('capell-admin::generic.primary'),
                                'secondary' => __('capell-admin::generic.secondary'),
                            ]),
                        Select::make('target')
                            ->label(__('capell-admin::form.url_target'))
                            ->options([
                                '_blank' => __('capell-admin::generic.new_tab'),
                            ]),
                    ]),
            ]);
    }

    private function getItemType(string $key): ?ActionLinkEnum
    {
        $type = $this->getRawState()[$key]['type'] ?? null;

        if ($type === null) {
            return null;
        }

        if ($type instanceof ActionLinkEnum) {
            return $type;
        }

        return ActionLinkEnum::from($type);
    }
}
