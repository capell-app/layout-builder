<?php

declare(strict_types=1);

namespace Capell\Backup\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface BackupRowContributor
{
    /**
     * Extra attributes to include in an exported row. Return [] when no
     * extra columns exist on the model's table.
     *
     * @return array<string, mixed>
     */
    public function extraAttributes(Model $model): array;

    /**
     * Strip attributes from an incoming imported row that core cannot persist.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function normalizeIncomingRow(array $attributes): array;

    /**
     * Apply any filtering the contributor requires to restrict the query
     * to rows that should be exportable. Core calls this and does not
     * describe what the contributor should filter by.
     */
    public function scopeExportable(Builder $query): Builder;
}
