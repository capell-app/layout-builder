<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Resources\CampaignGroups\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Campaigns\Enums\CampaignStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class CampaignGroupForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                TextInput::make('name')
                    ->label(__('capell-campaigns::form.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('capell-campaigns::form.slug'))
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->label(__('capell-campaigns::form.status'))
                    ->options(CampaignStatus::class)
                    ->required(),
                TextInput::make('site_id')
                    ->label(__('capell-campaigns::form.site'))
                    ->numeric(),
                DateTimePicker::make('starts_at')
                    ->label(__('capell-campaigns::form.starts_at')),
                DateTimePicker::make('ends_at')
                    ->label(__('capell-campaigns::form.ends_at')),
                TextInput::make('utm_source')
                    ->label(__('capell-campaigns::form.utm_source')),
                TextInput::make('utm_medium')
                    ->label(__('capell-campaigns::form.utm_medium')),
                TextInput::make('utm_campaign')
                    ->label(__('capell-campaigns::form.utm_campaign')),
                TextInput::make('budget_amount')
                    ->label(__('capell-campaigns::form.budget_amount'))
                    ->numeric(),
                Textarea::make('notes')
                    ->label(__('capell-campaigns::form.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
