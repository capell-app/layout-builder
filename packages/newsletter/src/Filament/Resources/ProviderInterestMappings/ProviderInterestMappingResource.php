<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\ProviderInterestMappings;

use BackedEnum;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Filament\Resources\ProviderInterestMappings\Pages\CreateProviderInterestMapping;
use Capell\Newsletter\Filament\Resources\ProviderInterestMappings\Pages\EditProviderInterestMapping;
use Capell\Newsletter\Filament\Resources\ProviderInterestMappings\Pages\ListProviderInterestMappings;
use Capell\Newsletter\Models\ProviderInterestMapping;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ProviderInterestMappingResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'remote_name';

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return $configurator->components([
            Select::make('provider_audience_id')
                ->relationship('providerAudience', 'name')
                ->label(__('capell-newsletter::navigation.provider_audiences'))
                ->required(),
            Select::make('tag_id')
                ->relationship(
                    'tag',
                    'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', config('capell-newsletter.newsletter_tag_type', 'newsletter')),
                )
                ->label(__('capell-newsletter::navigation.newsletter_tags'))
                ->required(),
            TextInput::make('remote_interest_id')->label(__('capell-newsletter::form.remote_interest_id'))->required(),
            TextInput::make('remote_interest_type')->label(__('capell-newsletter::form.remote_interest_type'))->required(),
            TextInput::make('remote_name')->label(__('capell-newsletter::form.remote_name')),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('providerAudience.name')->label(__('capell-newsletter::navigation.provider_audiences'))->sortable(),
            TextColumn::make('tag.name')->label(__('capell-newsletter::navigation.newsletter_tags')),
            TextColumn::make('remote_interest_id')->label(__('capell-newsletter::form.remote_interest_id'))->searchable(),
            TextColumn::make('remote_interest_type')->label(__('capell-newsletter::form.remote_interest_type'))->badge(),
        ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return ProviderInterestMapping::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('providerAudience.providerConnection', function (Builder $query): void {
                SiteScope::applyForCurrentActor($query);
            });
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.provider_interest_mappings');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviderInterestMappings::route('/'),
            'create' => CreateProviderInterestMapping::route('/create'),
            'edit' => EditProviderInterestMapping::route('/{record}/edit'),
        ];
    }
}
