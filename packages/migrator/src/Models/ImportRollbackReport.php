<?php

declare(strict_types=1);

namespace Capell\Migrator\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $import_session_id
 * @property int|null $user_id
 * @property string|null $source_filename
 * @property string|null $source_package_checksum
 * @property array<int, array{class: string, id: int|string}>|null $created_models
 * @property array<string, mixed>|null $summary
 * @property string $manual_instructions
 * @property CarbonImmutable|null $executed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ImportSession $importSession
 */
class ImportRollbackReport extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'import_rollback_reports';

    protected $fillable = [
        'uuid',
        'import_session_id',
        'user_id',
        'source_filename',
        'source_package_checksum',
        'created_models',
        'summary',
        'manual_instructions',
        'executed_at',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function importSession(): BelongsTo
    {
        return $this->belongsTo(ImportSession::class);
    }

    protected function casts(): array
    {
        return [
            'created_models' => 'array',
            'summary' => 'array',
            'executed_at' => 'immutable_datetime',
        ];
    }
}
