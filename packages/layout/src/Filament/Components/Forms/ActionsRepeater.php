<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Admin\Filament\Components\Forms\Site\SiteSelect;
use Capell\Core\Models\Page;
use Filament\Forms;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Get;

class ActionsRepeater extends Forms\Components\Repeater
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::generic.action'))
            ->statePath('actions')
            ->columnSpanFull()
            ->collapsed(function (?ComponentContainer $item): bool {
                $state = $item->getRawState();

                return filled($state['page_id']) || filled($state['url']);
            })
            ->cloneable()
            ->orderColumn()
            ->defaultItems(0)
            ->addActionLabel(__('capell-admin::button.add_action'))
            ->itemLabel(function (array $state): string {
                if (! empty($state['label'])) {
                    return $state['label'];
                }

                return match ($state['type']) {
                    'page' => Page::find($state['page_id'], ['name'])?->name,
                    'url' => $state['url'],
                    default => null
                } ?? __('capell-admin::generic.action');
            })
            ->schema([
                Forms\Components\Radio::make('type')
                    ->label(__('capell-admin::form.type'))
                    ->reactive()
                    ->required()
                    ->inline()
                    ->default('page')
                    ->hiddenLabel()
                    ->options([
                        'page' => __('capell-admin::generic.page'),
                        'url' => __('capell-admin::generic.url'),
                    ])
                    ->afterStateUpdated(function (Get $get, Forms\Set $set): void {
                        if ($get('type') === 'page') {
                            $set('url', null);
                        } else {
                            $set('page_uuid', null);
                        }
                    }),
                Forms\Components\Grid::make(['md' => 2, 'lg' => 3])
                    ->visible(fn (Get $get): bool => $get('type') === 'page')
                    ->schema([
                        PageSelect::make('page_uuid')
                            ->required()
                            ->reactive()
                            ->withUuid()
                            ->columnSpan(['lg' => 2]),
                        SiteSelect::make('site_id')
                            ->preload()
                            ->reactive(),
                    ]),

                Forms\Components\TextInput::make('url')
                    ->label(__('capell-admin::form.url'))
                    ->visible(fn (Get $get): bool => $get('type') === 'url')
                    ->validationAttribute(__('capell-admin::form.url'))
                    ->columnSpan(2)
                    ->required()
                    ->lazy(),

                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label(__('capell-admin::form.label'))
                            ->helperText(
                                fn (Get $get): ?string => $get('type') === 'page' || $get('page_id')
                                    ? __('capell-admin::generic.action_page_label_hint')
                                    : null
                            ),
                        Forms\Components\TextInput::make('icon')
                            ->label(__('capell-admin::form.icon'))
                            ->placeholder('heroicon-o-clock'),
                        Forms\Components\Select::make('color')
                            ->label(__('capell-admin::form.color'))
                            ->options([
                                'primary' => __('capell-admin::generic.primary'),
                                'secondary' => __('capell-admin::generic.secondary'),
                            ]),
                        Forms\Components\Select::make('target')
                            ->label(__('capell-admin::form.url_target'))
                            ->options([
                                '_blank' => __('capell-admin::generic.new_tab'),
                            ]),
                    ]),
            ]);
    }
}
