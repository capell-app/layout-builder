<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

final class CampaignCtaBlockForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                Select::make('campaign_group_id')
                    ->label(__('capell-campaigns::form.campaign_group'))
                    ->relationship('campaignGroup', 'name')
                    ->required(),
                TextInput::make('site_id')
                    ->label(__('capell-campaigns::form.site'))
                    ->numeric(),
                TextInput::make('name')
                    ->label(__('capell-campaigns::form.name'))
                    ->required(),
                TextInput::make('key')
                    ->label(__('capell-campaigns::form.key'))
                    ->required(),
                TextInput::make('headline')
                    ->label(__('capell-campaigns::form.headline')),
                Textarea::make('body')
                    ->label(__('capell-campaigns::form.body'))
                    ->columnSpanFull(),
                Repeater::make('actions')
                    ->label(__('capell-campaigns::form.actions'))
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->cloneable()
                    ->reorderable()
                    ->addActionLabel(__('capell-campaigns::form.add_action'))
                    ->itemLabel(fn (array $state): ?string => is_string($state['label'] ?? null) ? $state['label'] : null)
                    ->schema([
                        Grid::make(['default' => 1, 'lg' => 2])
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('capell-campaigns::form.action_label'))
                                    ->required(),
                                Select::make('style')
                                    ->label(__('capell-campaigns::form.action_style'))
                                    ->options([
                                        'primary' => __('capell-campaigns::generic.action_styles.primary'),
                                        'secondary' => __('capell-campaigns::generic.action_styles.secondary'),
                                    ])
                                    ->default('primary')
                                    ->required(),
                            ]),
                        TextInput::make('url')
                            ->label(__('capell-campaigns::form.action_url'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('goal_key')
                            ->label(__('capell-campaigns::form.goal_key'))
                            ->columnSpanFull(),
                        Fieldset::make(__('capell-campaigns::form.utm_parameters'))
                            ->statePath('utm')
                            ->columns(['default' => 1, 'lg' => 2])
                            ->schema([
                                TextInput::make('source')
                                    ->label(__('capell-campaigns::form.utm_source')),
                                TextInput::make('medium')
                                    ->label(__('capell-campaigns::form.utm_medium')),
                                TextInput::make('campaign')
                                    ->label(__('capell-campaigns::form.utm_campaign')),
                                TextInput::make('term')
                                    ->label(__('capell-campaigns::form.utm_term')),
                                TextInput::make('content')
                                    ->label(__('capell-campaigns::form.utm_content')),
                            ]),
                    ]),
                Fieldset::make(__('capell-campaigns::form.default_utm'))
                    ->statePath('default_utm')
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make('source')
                            ->label(__('capell-campaigns::form.utm_source')),
                        TextInput::make('medium')
                            ->label(__('capell-campaigns::form.utm_medium')),
                        TextInput::make('campaign')
                            ->label(__('capell-campaigns::form.utm_campaign')),
                        TextInput::make('term')
                            ->label(__('capell-campaigns::form.utm_term')),
                        TextInput::make('content')
                            ->label(__('capell-campaigns::form.utm_content')),
                    ]),
                Toggle::make('is_active')
                    ->label(__('capell-campaigns::form.is_active')),
            ]);
    }
}
