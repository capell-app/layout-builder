<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\ProviderAudiences;

use BackedEnum;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Filament\Resources\ProviderAudiences\Pages\CreateProviderAudience;
use Capell\Newsletter\Filament\Resources\ProviderAudiences\Pages\EditProviderAudience;
use Capell\Newsletter\Filament\Resources\ProviderAudiences\Pages\ListProviderAudiences;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ProviderAudienceResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return $configurator->components([
            Select::make('provider_connection_id')
                ->relationship('providerConnection', 'name')
                ->required(),
            TextInput::make('name')->label(__('capell-newsletter::form.name'))->required(),
            TextInput::make('remote_id')->label(__('capell-newsletter::form.remote_id'))->required(),
            Toggle::make('is_default')->default(false),
            Toggle::make('sync_subscribed_only')->default(true),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('capell-newsletter::form.name'))->searchable()->sortable(),
            TextColumn::make('providerConnection.name')->label(__('capell-newsletter::navigation.provider_connections'))->sortable(),
            TextColumn::make('remote_id')->label(__('capell-newsletter::form.remote_id'))->searchable(),
            TextColumn::make('updated_at')->label(__('capell-newsletter::table.updated_at'))->dateTime()->sortable(),
        ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return ProviderAudience::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('providerConnection', function (Builder $query): void {
                SiteScope::applyForCurrentActor($query);
            });
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.provider_audiences');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviderAudiences::route('/'),
            'create' => CreateProviderAudience::route('/create'),
            'edit' => EditProviderAudience::route('/{record}/edit'),
        ];
    }
}
