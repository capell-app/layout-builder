<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Filament\Widgets;

use Capell\Admin\Contracts\CapellWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\AuthenticationLog\Actions\BuildAuthenticationLogsQueryAction;
use Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\AuthenticationLogResource;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

final class AuthenticationLogsWidget extends BaseWidget implements CapellWidgetContract
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['admin', 'super_admin'];

    protected static string $settingsKey = 'authentication_logs';

    /** @var int|string|array<string, int|string|null> */
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->paginationPageOptions([5])
            ->queryStringIdentifier('authentication-logs')
            ->searchable(false)
            ->heading(__('capell-admin::heading.authentication_logs'))
            ->columns($this->getTableColumns())
            ->defaultSort('login_at', 'desc')
            ->recordClasses(
                fn (AuthenticationLog $record): ?string => $record->authenticatable === null ? 'table-row-warning' : null,
            )
            ->headerActions([
                Action::make('view-all')
                    ->label(__('capell-admin::button.view_all'))
                    ->button()
                    ->color('gray')
                    ->url(AuthenticationLogResource::getUrl()),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return BuildAuthenticationLogsQueryAction::run();
    }

    protected function getTableColumns(): array
    {
        return [
            Split::make([
                Stack::make([
                    TextColumn::make('authenticatable.name')
                        ->label(__('capell-admin::table.user_who_logged_in'))
                        ->url(fn (AuthenticationLog $record): string => $this->getFilamentUrl($record))
                        ->color(fn (AuthenticationLog $record): ?string => $record->authenticatable === null ? 'danger' : null)
                        ->getStateUsing(
                            function (AuthenticationLog $record): string {
                                if ($record->authenticatable === null) {
                                    return __('capell-admin::generic.missing');
                                }

                                $name = (string) data_get($record->authenticatable, 'name', '');
                                $email = (string) data_get($record->authenticatable, 'email', '');

                                return Str::of($name)
                                    ->when($email !== '', fn (Stringable $str): Stringable => $str->append(' (' . $email . ')'))
                                    ->toString();
                            },
                        ),
                    TextColumn::make('ip_address')
                        ->label(trans('filament-authentication-log::filament-authentication-log.column.ip_address')),
                    TextColumn::make('location')
                        ->visible(fn (): bool => config('authentication-log.notifications.new-device.location', false)),
                ]),
                TextColumn::make('last_active_at')
                    ->alignment(Alignment::End)
                    ->grow(false)
                    ->getStateUsing(function (TextColumn $column, AuthenticationLog $record): ?HtmlString {
                        $date = $record->last_seen_at ?? $record->login_at;

                        if ($date === null) {
                            return null;
                        }

                        $loginAt = $date->setTimezone($column->getTimezone());
                        $formattedDate = $loginAt->translatedFormat($column->getTable()->getDefaultDateTimeDisplayFormat());
                        $tooltip = __('capell-admin::generic.last_seen_at', ['date' => $formattedDate]);
                        $label = $loginAt->isSameMinute(now($loginAt->getTimezone())) ? __('capell-admin::generic.online_now') : $loginAt->diffForHumans();

                        return new HtmlString(
                            <<<BLADE
                            <span x-tooltip.raw="{$tooltip}">
                              {$label}
                            </span>
                        BLADE
                        );
                    }),
            ]),
        ];
    }

    protected function paginateTableQuery(Builder $query): CursorPaginator
    {
        return $query->cursorPaginate(
            perPage: ($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage(),
            cursorName: (in_array($this->getTable()->getQueryStringIdentifier(), [null, '', '0'], true) ? 'authentication-logs' : $this->getTable()->getQueryStringIdentifier()) . '_cursor',
        );
    }

    private function getFilamentUrl(AuthenticationLog $record): string
    {
        if ($record->authenticatable === null) {
            return '';
        }

        return route(
            'filament.' . Filament::getCurrentOrDefaultPanel()->getId() . '.resources.' . Str::plural(Str::lower(class_basename($record->authenticatable::class))) . '.edit',
            ['record' => $record->authenticatable_id],
        );
    }
}
