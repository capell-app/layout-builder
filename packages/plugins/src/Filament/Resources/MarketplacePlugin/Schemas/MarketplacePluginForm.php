<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources\MarketplacePlugin\Schemas;

use ArrayObject;
use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                                TextInput::make('name')
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
                                TextInput::make('homepage_url')
                                    ->label(__('Homepage URL'))
                                    ->url()
                                    ->columnSpan(1),
                                TextInput::make('documentation_url')
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

                Section::make(__('Pricing & Distribution'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('price_once')
                                    ->label(__('One-time Price'))
                                    ->integer()
                                    ->helperText(__('Whole dollars only — the column is an integer and fractional input truncates.'))
                                    ->columnSpan(1),
                                TextInput::make('price_monthly')
                                    ->label(__('Monthly Price'))
                                    ->integer()
                                    ->helperText(__('Whole dollars only — the column is an integer and fractional input truncates.'))
                                    ->columnSpan(1),
                                TextInput::make('price_yearly')
                                    ->label(__('Yearly Price'))
                                    ->integer()
                                    ->helperText(__('Whole dollars only — the column is an integer and fractional input truncates.'))
                                    ->columnSpan(1),
                                TextInput::make('trial_days')
                                    ->label(__('Trial Days'))
                                    ->integer()
                                    ->columnSpan(1),
                                TextInput::make('anystack_product_id')
                                    ->label(__('Anystack Product ID'))
                                    ->helperText(__('Per-product subdomain on composer.sh.'))
                                    ->columnSpan(1),
                                TextInput::make('latest_version')
                                    ->label(__('Latest Version'))
                                    ->placeholder('1.0.0')
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
                            ->helperText(__('JSON object describing compatibility requirements.'))
                            ->dehydrateStateUsing(fn ($state): array => self::decodeJsonInput($state))
                            ->formatStateUsing(fn ($state): string => self::encodeJsonForDisplay($state)),
                        FormTextarea::make('capabilities')
                            ->label(__('Capabilities'))
                            ->helperText(__('JSON array of capability strings for validation.'))
                            ->dehydrateStateUsing(fn ($state): array => self::decodeJsonInput($state))
                            ->formatStateUsing(fn ($state): string => self::encodeJsonForDisplay($state)),
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

    /**
     * Convert a textarea JSON string (or any state) into an array suitable for
     * the AsArrayObject-cast model columns. Empty / invalid input becomes [].
     *
     * @return array<int|string, mixed>
     */
    public static function decodeJsonInput(mixed $state): array
    {
        if (is_array($state)) {
            return $state;
        }

        if ($state instanceof ArrayObject) {
            return $state->getArrayCopy();
        }

        if (! is_string($state) || trim($state) === '') {
            return [];
        }

        $decoded = json_decode($state, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Format an AsArrayObject / array / string value for display in a JSON
     * textarea. Strings pass through unchanged; arrays pretty-print.
     */
    public static function encodeJsonForDisplay(mixed $state): string
    {
        if ($state === null || $state === '') {
            return '';
        }

        if (is_string($state)) {
            return $state;
        }

        if ($state instanceof ArrayObject) {
            $state = $state->getArrayCopy();
        }

        if (! is_array($state)) {
            return '';
        }

        $encoded = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }
}
