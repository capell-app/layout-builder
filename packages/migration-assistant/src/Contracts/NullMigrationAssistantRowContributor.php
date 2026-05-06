<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class NullMigrationAssistantRowContributor implements MigrationAssistantRowContributor
{
    public function extraAttributes(Model $model): array
    {
        return [];
    }

    public function normalizeIncomingRow(array $attributes): array
    {
        return $attributes;
    }

    public function scopeExportable(Builder $query): Builder
    {
        return $query;
    }
}
