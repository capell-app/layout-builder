<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Actions\DashboardReports;

use Capell\MediaLibrary\Models\CuratorMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildMediaHealthQueryAction
{
    use AsAction;

    /**
     * @param  array<int, array{table: string, column: string}>|null  $ownerForeignKeys
     */
    public function handle(?array $ownerForeignKeys = null): Builder
    {
        if (! Schema::hasTable('curator')) {
            return $this->emptyCuratorQuery();
        }

        $staleThreshold = now()->subDays(90);
        $knownOwnerForeignKeys = $this->knownOwnerForeignKeys(
            $ownerForeignKeys ?? config('capell.media_library.owner_foreign_keys', []),
        );
        $usageCountExpression = $this->usageCountExpression($knownOwnerForeignKeys);

        return CuratorMedia::query()
            ->select('curator.*')
            ->selectRaw($usageCountExpression . ' as usage_count')
            ->where(function (Builder $nestedCuratorQuery) use ($knownOwnerForeignKeys, $staleThreshold, $usageCountExpression): void {
                $nestedCuratorQuery
                    ->whereNull('alt')
                    ->orWhere('alt', '')
                    ->orWhere('updated_at', '<', $staleThreshold);

                if ($knownOwnerForeignKeys !== []) {
                    $nestedCuratorQuery->orWhereRaw('(' . $usageCountExpression . ') = 0');
                }
            });
    }

    private function emptyCuratorQuery(): Builder
    {
        $query = CuratorMedia::query();
        $emptyCuratorTable = DB::query()
            ->selectRaw($this->emptyCuratorColumns())
            ->whereRaw('1 = 0');

        $query->getQuery()->fromSub($emptyCuratorTable, 'curator');

        return $query
            ->select('curator.*')
            ->selectRaw('0 as usage_count');
    }

    private function emptyCuratorColumns(): string
    {
        return implode(', ', [
            'null as id',
            'null as disk',
            'null as directory',
            'null as visibility',
            'null as name',
            'null as path',
            'null as width',
            'null as height',
            'null as size',
            'null as type',
            'null as ext',
            'null as alt',
            'null as title',
            'null as description',
            'null as caption',
            'null as pretty_name',
            'null as exif',
            'null as curations',
            'null as created_at',
            'null as updated_at',
        ]);
    }

    /**
     * @return array<int, array{table: string, column: string}>
     */
    private function knownOwnerForeignKeys(mixed $configuredOwnerForeignKeys): array
    {
        if (! is_array($configuredOwnerForeignKeys)) {
            return [];
        }

        $ownerForeignKeys = [];

        foreach ($configuredOwnerForeignKeys as $configuredOwnerForeignKey) {
            if (! is_array($configuredOwnerForeignKey)) {
                continue;
            }

            $table = $configuredOwnerForeignKey['table'] ?? null;
            $column = $configuredOwnerForeignKey['column'] ?? null;
            if (! is_string($table)) {
                continue;
            }

            if (! is_string($column)) {
                continue;
            }

            if (! $this->isSafeIdentifier($table)) {
                continue;
            }

            if (! $this->isSafeIdentifier($column)) {
                continue;
            }

            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            $ownerForeignKeys[] = [
                'table' => $table,
                'column' => $column,
            ];
        }

        return $ownerForeignKeys;
    }

    private function isSafeIdentifier(string $identifier): bool
    {
        return preg_match('/^\w+$/', $identifier) === 1;
    }

    /**
     * @param  array<int, array{table: string, column: string}>  $ownerForeignKeys
     */
    private function usageCountExpression(array $ownerForeignKeys): string
    {
        if ($ownerForeignKeys === []) {
            return '0';
        }

        $grammar = DB::connection()->getQueryGrammar();
        $curatorIdColumn = $grammar->wrap('curator.id');
        $usageQueries = [];

        foreach ($ownerForeignKeys as $ownerForeignKey) {
            $usageQueries[] = sprintf(
                '(select count(*) from %s where %s = %s)',
                $grammar->wrapTable($ownerForeignKey['table']),
                $grammar->wrap($ownerForeignKey['column']),
                $curatorIdColumn,
            );
        }

        return implode(' + ', $usageQueries);
    }
}
