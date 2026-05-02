<?php

declare(strict_types=1);

namespace Capell\Backup\Models;

use Capell\Backup\Enums\ImportSessionKind;
use Capell\Backup\Enums\ImportSessionStatus;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Userstampable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property ImportSessionKind $kind
 * @property ImportSessionStatus $status
 * @property string|null $source_environment
 * @property string|null $source_filename
 * @property string|null $source_package_path
 * @property string|null $source_package_checksum
 * @property string|null $working_dir
 * @property array<string, mixed>|null $manifest
 * @property array<string, mixed>|null $resolution_map
 * @property array<string, mixed>|null $page_decisions
 * @property array<string, mixed>|null $relation_decisions
 * @property array<string, mixed>|null $validation_results
 * @property array<string, mixed>|null $result_summary
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $validated_at
 * @property CarbonImmutable|null $executed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 */
class ImportSession extends Model implements Userstampable
{
    use HasFactory;
    use HasUserstamps;
    use HasUuids;

    protected $table = 'import_sessions';

    protected $fillable = [
        'uuid',
        'user_id',
        'kind',
        'status',
        'source_environment',
        'source_filename',
        'source_package_path',
        'source_package_checksum',
        'working_dir',
        'manifest',
        'resolution_map',
        'page_decisions',
        'relation_decisions',
        'validation_results',
        'result_summary',
        'failure_reason',
        'reviewed_at',
        'resolved_at',
        'validated_at',
        'executed_at',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'kind' => ImportSessionKind::class,
            'status' => ImportSessionStatus::class,
            'manifest' => 'array',
            'resolution_map' => 'array',
            'page_decisions' => 'array',
            'relation_decisions' => 'array',
            'validation_results' => 'array',
            'result_summary' => 'array',
            'reviewed_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
            'validated_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
        ];
    }
}
