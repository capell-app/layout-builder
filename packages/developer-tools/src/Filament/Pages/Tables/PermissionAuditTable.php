<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Pages\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\DeveloperTools\Actions\Reports\BuildPermissionAuditQueryAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PermissionAuditTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildPermissionAuditQueryAction::run())
            ->columns([
                TextColumn::make('name')
                    ->label('Role')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->sortable(),
            ])
            ->striped()
            ->paginated();
    }
}
