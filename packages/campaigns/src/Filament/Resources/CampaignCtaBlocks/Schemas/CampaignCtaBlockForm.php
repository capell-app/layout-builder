<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\Schemas;

use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class CampaignCtaBlockForm implements FormConfigurator
{
    public static function configure(Schema $configurator): Schema
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
                Toggle::make('is_active')
                    ->label(__('capell-campaigns::form.is_active')),
            ]);
    }
}
