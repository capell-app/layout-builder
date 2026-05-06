<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Filament\Resources\LoginAudits\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\LoginAudit\Models\LoginAudit;
use Filament\Facades\Filament;
use Filament\FormBuilder\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginAuditsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['authenticatable']))
            ->defaultSort('login_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters(self::getTableFilters());
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            TextColumn::make('authenticatable')
                ->label(__('capell-admin::table.user_who_logged_in'))
                ->color(fn (LoginAudit $record): ?string => $record->authenticatable === null ? 'danger' : null)
                ->getStateUsing(fn (LoginAudit $record): string => self::getAuthenticatableName($record))
                ->url(fn (LoginAudit $record): ?string => self::getAuthenticatableUrl($record))
                ->sortable(),
            TextColumn::make('ip_address')
                ->label(trans('filament-login-audit::filament-login-audit.column.ip_address'))
                ->searchable()
                ->sortable(),
            TextColumn::make('user_agent')
                ->label(trans('filament-login-audit::filament-login-audit.column.user_agent'))
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(50)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    if (mb_strlen($state) <= $column->getCharacterLimit()) {
                        return null;
                    }

                    return $state;
                })
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('login_at')
                ->label(trans('filament-login-audit::filament-login-audit.column.login_at'))
                ->icon(fn (LoginAudit $record): string => $record->login_successful ? 'heroicon-s-check-circle' : 'heroicon-s-x-circle'),
            DateColumn::make('logout_at')
                ->label(trans('filament-login-audit::filament-login-audit.column.logout_at'))
                ->icon(fn (LoginAudit $record): string => $record->cleared_by_user ? 'heroicon-s-check-circle' : 'heroicon-s-x-circle')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('location')
                ->label(__('capell-admin::table.location'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Filter::make('login_successful')
                ->toggle()
                ->query(fn (Builder $query): Builder => $query->where('login_successful', true)),
            Filter::make('login_at')
                ->schema([
                    DatePicker::make('login_from')
                        ->label(__('capell-admin::filter.login_from')),
                    DatePicker::make('login_until')
                        ->label(__('capell-admin::filter.login_until')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when(
                        $data['login_from'],
                        fn (Builder $query, string $date): Builder => $query->whereDate('login_at', '>=', $date),
                    )
                    ->when(
                        $data['login_until'],
                        fn (Builder $query, string $date): Builder => $query->whereDate('login_at', '<=', $date),
                    )),
            Filter::make('cleared_by_user')
                ->toggle()
                ->query(fn (Builder $query): Builder => $query->where('cleared_by_user', true)),
        ];
    }

    private static function getAuthenticatableName(LoginAudit $record): string
    {
        $authenticatable = $record->authenticatable;

        if (! $authenticatable instanceof Model) {
            return (string) __('capell-admin::generic.missing');
        }

        $name = $authenticatable->getAttribute('name');

        if (blank($name)) {
            return (string) __('capell-admin::generic.missing');
        }

        return (string) $name;
    }

    private static function getAuthenticatableUrl(LoginAudit $record): ?string
    {
        $authenticatable = $record->authenticatable;

        if (! $authenticatable instanceof Model) {
            return null;
        }

        return route(
            'filament.' . Filament::getCurrentOrDefaultPanel()->getId() . '.resources.' . Str::plural(Str::lower(class_basename($authenticatable::class))) . '.edit',
            ['record' => $authenticatable->getKey()],
        );
    }
}
