<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\SafeAdminUrl;
use Capell\SeoSuite\Actions\DashboardReports\BuildBrokenLinksQueryAction;
use Capell\SeoSuite\Filament\Actions\CreateRedirectFromBrokenLinkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrokenLinksTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildBrokenLinksQueryAction::run())
            ->columns([
                PageNameColumn::make('page.name')
                    ->label(__('capell-admin::table.page'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_url')
                    ->label(__('capell-admin::table.link_target'))
                    ->size('sm')
                    ->url(fn (?string $state): ?string => SafeAdminUrl::href($state))
                    ->openUrlInNewTab()
                    ->searchable(),
                TextColumn::make('http_status')
                    ->label(__('capell-admin::table.http_status'))
                    ->badge()
                    ->size('sm')
                    ->sortable(),
                DateColumn::make('last_checked_at')
                    ->label(__('capell-admin::table.last_checked'))
                    ->size('sm')
                    ->sortable(),
            ])
            ->recordActions([
                CreateRedirectFromBrokenLinkAction::make(),
            ])
            ->defaultSort('last_checked_at', 'desc');
    }
}
