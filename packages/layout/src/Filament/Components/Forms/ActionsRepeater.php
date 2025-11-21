<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Admin\Filament\Components\Forms\Site\SiteSelect;
use Capell\Core\Models\Page;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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

                return ! empty($state['page_id']) || ! empty($state['url']);
            })
            ->cloneable()
            ->orderColumn()
            ->defaultItems(0)
            ->addActionLabel(__('capell-layout::button.add_action'))
            ->itemLabel(function (array $state): string {
                if (! empty($state['label'])) {
                    return $state['label'];
                }

                return match ($state['type']) {
                    'page' => Page::query()->find($state['page_id'], ['name'])?->name,
                    'url' => $state['url'],
                    default => null
                } ?? __('capell-admin::generic.action');
            })
            ->schema([
                Radio::make('type')
                    ->label(__('capell-admin::form.type'))
                    ->required()
                    ->inline()
                    ->default('page')
                    ->hiddenLabel()
                    ->options([
                        'page' => __('capell-admin::generic.page'),
                        'url' => __('capell-admin::generic.url'),
                    ])
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        if ($get('type') === 'page') {
                            $set('url', null);
                        } else {
                            $set('page_id', null);
                        }
                    }),
                Grid::make(['md' => 2, 'lg' => 3])
                    ->visibleJs(<<<'JS'
                         $get('type') === 'page'
                    JS)
                    ->schema([
                        PageSelect::make('page_id')
                            ->required()
                            ->reactive()
                            ->columnSpan(['lg' => 2]),
                        SiteSelect::make('site_id')
                            ->preload()
                            ->reactive(),
                    ]),

                TextInput::make('url')
                    ->label(__('capell-admin::form.url'))
                    ->visibleJs(<<<'JS'
                         $get('type') === 'url'
                    JS)
                    ->validationAttribute(__('capell-admin::form.url'))
                    ->columnSpan(2)
                    ->required()
                    ->lazy(),

                Grid::make()
                    ->schema([
                        TextInput::make('label')
                            ->label(__('capell-admin::form.label')),
                        IconPicker::make('icon')
                            ->label(__('capell-admin::form.icon')),
                        Select::make('color')
                            ->label(__('capell-layout::form.color'))
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
}
