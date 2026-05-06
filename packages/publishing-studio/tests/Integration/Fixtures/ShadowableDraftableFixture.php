<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Tests\Integration\Fixtures;

use Capell\PublishingStudio\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Draftable fixture that mirrors the shape of a real workspace-aware model:
 * carries both `shadowed_by_workspace_id` and `SoftDeletes` so the
 * copy-on-write action can be exercised end-to-end (edit fork + delete
 * tombstone + shadow maintenance).
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $shadowed_by_workspace_id
 * @property string $uuid
 * @property string $name
 * @property CarbonImmutable|null $deleted_at
 */
class ShadowableDraftableFixture extends Model
{
    use BelongsToWorkspace;
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'shadowable_draftable_fixtures';

    protected $fillable = ['uuid', 'name', 'workspace_id', 'shadowed_by_workspace_id'];
}
