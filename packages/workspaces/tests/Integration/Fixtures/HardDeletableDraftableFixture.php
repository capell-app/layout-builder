<?php

declare(strict_types=1);

namespace Capell\Workspaces\Tests\Integration\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Draftable fixture that deliberately omits {@see SoftDeletes}
 * so the `cloneForDelete` guard in {@see CopyOnWriteAction}
 * can be covered: deleting a live row inside a workspace context must throw
 * on any model that can't represent a tombstone.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $shadowed_by_workspace_id
 * @property string $uuid
 * @property string $name
 */
class HardDeletableDraftableFixture extends Model
{
    // use BelongsToWorkspace;
    use HasFactory;

    public $timestamps = true;

    protected $table = 'hard_deletable_draftable_fixtures';

    protected $fillable = ['uuid', 'name', 'workspace_id', 'shadowed_by_workspace_id'];
}
