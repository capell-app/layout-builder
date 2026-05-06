<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A reviewer comment attached to a single field on a workspace-scoped entity.
 * `field_path` is a dot-notation path inside the entity row (e.g. `title`,
 * `settings.seo.description`) so threads can be attached to leaves deep
 * inside a JSON column.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $entity_type
 * @property string $entity_uuid
 * @property string $field_path
 * @property string|null $author_type
 * @property int|null $author_id
 * @property string $body
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Workspace $workspace
 */
class WorkspaceFieldComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'entity_type',
        'entity_uuid',
        'field_path',
        'author_type',
        'author_id',
        'body',
        'resolved_at',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function resolve(?CarbonImmutable $at = null): void
    {
        $this->forceFill(['resolved_at' => $at ?? CarbonImmutable::now()])->save();
    }

    public function reopen(): void
    {
        $this->forceFill(['resolved_at' => null])->save();
    }

    protected function casts(): array
    {
        return [
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
