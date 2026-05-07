<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\ImportBatches;

use BackedEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Filament\Concerns\ScopesNewsletterResourcesToAssignedSites;
use Capell\Newsletter\Filament\Resources\ImportBatches\Pages\ListImportBatches;
use Capell\Newsletter\Models\ImportBatch;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ImportBatchResource extends Resource
{
    use ScopesNewsletterResourcesToAssignedSites;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('filename')->label(__('capell-newsletter::form.name'))->searchable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('total_rows')->sortable(),
                TextColumn::make('valid_rows')->sortable(),
                TextColumn::make('invalid_rows')->sortable(),
                TextColumn::make('created_at')->label(__('capell-newsletter::table.created_at'))->dateTime()->sortable(),
            ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return ImportBatch::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return self::applyNewsletterSiteScope(parent::getEloquentQuery());
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Marketing';
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.import_batches');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportBatches::route('/'),
        ];
    }
}
