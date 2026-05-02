<?php

declare(strict_types=1);

namespace Capell\Backup\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * H6 placeholder. Tracks full-environment restore jobs — analogous to
 * {@see ImportSession} but for the
 * FullRestore kind. The migration creates the minimal column set so the
 * morph map and relations in admin can reference it from H2.x onward;
 * lifecycle transitions (status enum, queued/running/completed flow)
 * ship alongside {@see RestoreService}
 * in phase H6.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property string $status
 * @property string|null $source_archive_path
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class BackupRestore extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'backup_restores';

    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'source_archive_path',
        'failure_reason',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
