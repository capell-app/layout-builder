<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources\MarketplacePlugin\Schemas;

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarketplacePluginForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('Basic Information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('slug')
                                    ->label(__('Slug'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash()
                                    ->columnSpan(1),
                                TextInput::make('composer_name')
                                    ->label(__('Composer Name'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText(__('Format: vendor/package'))
                                    ->columnSpan(1),
                                TextInput::make('title')
                                    ->label(__('Title'))
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(1),
                                TextInput::make('vendor')
                                    ->label(__('Vendor'))
                                    ->required()
                                    ->columnSpan(1),
                                Select::make('kind')
                                    ->label(__('Plugin Kind'))
                                    ->options(PluginKind::class)
                                    ->required()
                                    ->columnSpan(1),
                                Select::make('license_model')
                                    ->label(__('License Model'))
                                    ->options(LicenseModel::class)
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                        FormTextarea::make('description')
                            ->label(__('Description'))
                            ->columnSpan('full'),
                    ]),

                Section::make(__('URLs & Support'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('icon_url')
                                    ->label(__('Icon URL'))
                                    ->url()
                                    ->columnSpan(1),
                                TextInput::make('docs_url')
                                    ->label(__('Documentation URL'))
                                    ->url()
                                    ->columnSpan(1),
                                TextInput::make('purchase_url')
                                    ->label(__('Purchase URL'))
                                    ->url()
                                    ->columnSpan(1),
                                TextInput::make('support_email')
                                    ->label(__('Support Email'))
                                    ->email()
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make(__('Pricing'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('price_once')
                                    ->label(__('One-time Price'))
                                    ->numeric()
                                    ->columnSpan(1),
                                TextInput::make('price_monthly')
                                    ->label(__('Monthly Price'))
                                    ->numeric()
                                    ->columnSpan(1),
                                TextInput::make('price_yearly')
                                    ->label(__('Yearly Price'))
                                    ->numeric()
                                    ->columnSpan(1),
                                TextInput::make('currency')
                                    ->label(__('Currency'))
                                    ->default('USD')
                                    ->maxLength(3)
                                    ->columnSpan(1),
                                TextInput::make('trial_days')
                                    ->label(__('Trial Days'))
                                    ->numeric()
                                    ->columnSpan(1),
                                TextInput::make('anystack_product_id')
                                    ->label(__('Anystack Product ID'))
                                    ->uuid()
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make(__('Metadata'))
                    ->schema([
                        TagsInput::make('categories')
                            ->label(__('Categories'))
                            ->separator(','),
                        TagsInput::make('screenshots')
                            ->label(__('Screenshot URLs'))
                            ->separator(','),
                        FormTextarea::make('compatibility')
                            ->label(__('Compatibility JSON'))
                            ->helperText(__('JSON object describing compatibility requirements')),
                        FormTextarea::make('capabilities')
                            ->label(__('Capabilities'))
                            ->helperText(__('JSON array of capability strings for validation')),
                    ]),

                Section::make(__('Visibility & Sorting'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label(__('Visible in Marketplace'))
                                    ->default(true)
                                    ->columnSpan(1),
                                TextInput::make('sort_order')
                                    ->label(__('Sort Order'))
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }
}
