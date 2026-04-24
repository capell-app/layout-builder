<?php

declare(strict_types=1);

namespace Capell\Workspaces\Tests\Integration\Fixtures;

use Capell\Workspaces\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Minimal fixture model used by the Publisher / Rebaser / scope integration
 * tests. A dedicated table lets us exercise workspace behaviour without
 * dragging in every relationship of the real draftable models.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $uuid
 * @property string $name
 */
class WorkspaceDraftableFixture extends Model
{
    use BelongsToWorkspace;
    use HasFactory;

    public $timestamps = true;

    protected $table = 'workspace_draftable_fixtures';

    protected $fillable = ['uuid', 'name', 'workspace_id', 'shadowed_by_workspace_id'];
}
