<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Filament\Resources\CampaignGroups\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\CampaignStudio\Enums\CampaignStatus;
use Filament\FormBuilder\Components\DateTimePicker;
use Filament\FormBuilder\Components\Select;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Schema;

final class CampaignGroupForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        return $configurator
            ->columns(['default' => 1, 'lg' => 2])
            ->schema([
                TextInput::make('name')
                    ->label(__('capell-campaign-studio::form.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label(__('capell-campaign-studio::form.slug'))
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->label(__('capell-campaign-studio::form.status'))
                    ->options(CampaignStatus::class)
                    ->required(),
                TextInput::make('site_id')
                    ->label(__('capell-campaign-studio::form.site'))
                    ->numeric(),
                DateTimePicker::make('starts_at')
                    ->label(__('capell-campaign-studio::form.starts_at')),
                DateTimePicker::make('ends_at')
                    ->label(__('capell-campaign-studio::form.ends_at')),
                TextInput::make('utm_source')
                    ->label(__('capell-campaign-studio::form.utm_source')),
                TextInput::make('utm_medium')
                    ->label(__('capell-campaign-studio::form.utm_medium')),
                TextInput::make('utm_campaign')
                    ->label(__('capell-campaign-studio::form.utm_campaign')),
                TextInput::make('budget_amount')
                    ->label(__('capell-campaign-studio::form.budget_amount'))
                    ->numeric(),
                Textarea::make('notes')
                    ->label(__('capell-campaign-studio::form.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
