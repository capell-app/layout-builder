<?php

declare(strict_types=1);

namespace Capell\Workspaces\Models;

use Capell\Core\Database\Factories\WorkspaceFactory;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Workspaces\Data\WorkspaceSettingsData;
use Capell\Workspaces\Enums\WorkspaceApprovalActionEnum;
use Capell\Workspaces\Enums\WorkspaceKindEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Enums\WorkspaceTransitionEnum;
use Capell\Workspaces\Events\WorkspaceEventDispatcher;
use Capell\Workspaces\Events\WorkspaceStateChanged;
use Capell\Workspaces\WorkspaceContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as AuthenticatedUser;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $color
 * @property WorkspaceStatusEnum $status
 * @property WorkspaceKindEnum $kind
 * @property int|null $base_version_id
 * @property int|null $cloned_from_id
 * @property WorkspaceSettingsData|null $settings
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Version|null $baseVersion
 * @property-read Version|null $publishedVersion
 * @property-read Collection<int, WorkspaceApproval> $approvals
 */
class Workspace extends Model implements Userstampable
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    use HasUserstamps;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'color',
        'status',
        'kind',
        'base_version_id',
        'cloned_from_id',
        'settings',
        'submitted_at',
        'approved_at',
        'publish_at',
        'published_at',
    ];

    protected static string $factory = WorkspaceFactory::class;

    protected $attributes = [
        'status' => 'open',
        'kind' => 'manual',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function baseVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'base_version_id');
    }

    public function publishedVersion(): HasOne
    {
        return $this->hasOne(Version::class, 'source_workspace_id');
    }

    /** @return HasMany<WorkspaceApproval, self> */
    public function approvals(): HasMany
    {
        return $this->hasMany(WorkspaceApproval::class);
    }

    /** @return HasOne<WorkspaceApproval, self> */
    public function latestApproval(): HasOne
    {
        return $this->hasOne(WorkspaceApproval::class)->latestOfMany();
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isStale(): bool
    {
        $liveVersionId = Version::liveId();

        return $liveVersionId !== null
            && $this->base_version_id !== null
            && $this->base_version_id < $liveVersionId;
    }

    public function submitForApproval(AuthenticatedUser $user, ?string $notes = null): void
    {
        $previousStatus = $this->status;

        $this->status = WorkspaceStatusEnum::InReview;
        $this->submitted_at = now();
        $this->save();

        $this->approvals()->create([
            'actionable_type' => $user->getMorphClass(),
            'actionable_id' => $user->getKey(),
            'level' => 1,
            'action' => WorkspaceApprovalActionEnum::Submitted->value,
            'notes' => $notes,
        ]);

        event(new WorkspaceStateChanged($this, $previousStatus, $this->status, WorkspaceTransitionEnum::Submitted->value, $user, $notes));
    }

    public function approve(AuthenticatedUser $user, int $level, ?string $notes = null): void
    {
        $previousStatus = $this->status;

        $this->approvals()->create([
            'actionable_type' => $user->getMorphClass(),
            'actionable_id' => $user->getKey(),
            'level' => $level,
            'action' => WorkspaceApprovalActionEnum::Approved->value,
            'notes' => $notes,
        ]);

        $requiredLevels = $this->settings?->requiredApprovalLevels ?? 2;

        if ($level >= $requiredLevels) {
            $this->status = WorkspaceStatusEnum::Approved;
            $this->approved_at = now();
            $this->save();

            event(new WorkspaceStateChanged($this, $previousStatus, $this->status, WorkspaceTransitionEnum::Approved->value, $user, $notes));
        }
    }

    public function reject(AuthenticatedUser $user, int $level, string $notes): void
    {
        $previousStatus = $this->status;

        $this->approvals()->create([
            'actionable_type' => $user->getMorphClass(),
            'actionable_id' => $user->getKey(),
            'level' => $level,
            'action' => WorkspaceApprovalActionEnum::Rejected->value,
            'notes' => $notes,
        ]);

        $this->status = WorkspaceStatusEnum::Open;
        $this->submitted_at = null;
        $this->save();

        event(new WorkspaceStateChanged($this, $previousStatus, $this->status, WorkspaceTransitionEnum::Rejected->value, $user, $notes));
    }

    public function requestChanges(AuthenticatedUser $user, int $level, string $notes): void
    {
        $previousStatus = $this->status;

        $this->approvals()->create([
            'actionable_type' => $user->getMorphClass(),
            'actionable_id' => $user->getKey(),
            'level' => $level,
            'action' => WorkspaceApprovalActionEnum::ChangesRequested->value,
            'notes' => $notes,
        ]);

        $this->status = WorkspaceStatusEnum::Open;
        $this->submitted_at = null;
        $this->save();

        event(new WorkspaceStateChanged($this, $previousStatus, $this->status, WorkspaceTransitionEnum::ChangesRequested->value, $user, $notes));
    }

    public function markAbandoned(): void
    {
        $previousStatus = $this->status;

        $this->status = WorkspaceStatusEnum::Abandoned;
        $this->save();

        event(new WorkspaceStateChanged($this, $previousStatus, $this->status, WorkspaceTransitionEnum::Abandoned->value));
    }

    /**
     * Execute a callback with this workspace as the active context, then
     * restore whatever context was set before. Used by publish, rebase,
     * preview, and any admin operation that should see the workspace view.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runInContext(callable $callback): mixed
    {
        $previousWorkspace = WorkspaceContext::current();
        WorkspaceContext::set($this);

        try {
            return $callback();
        } finally {
            WorkspaceContext::set($previousWorkspace);
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('workspace')
            ->logAll()
            ->logExcept(['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Workspace $workspace): ?bool {
            /** @var WorkspaceEventDispatcher $dispatcher */
            $dispatcher = resolve(WorkspaceEventDispatcher::class);

            if (! $dispatcher->beforeDelete($workspace)) {
                return false;
            }

            return null; // Continue with deletion
        });

        static::deleted(function (Workspace $workspace): void {
            /** @var WorkspaceEventDispatcher $dispatcher */
            $dispatcher = resolve(WorkspaceEventDispatcher::class);

            $dispatcher->afterDelete($workspace);
        });
    }

    protected static function booted(): void
    {
        static::creating(function (self $workspace): void {
            if ($workspace->slug === null || $workspace->slug === '') {
                $workspace->slug = Str::slug($workspace->name) . '-' . Str::lower(Str::random(6));
            }

            if ($workspace->base_version_id === null) {
                $liveVersion = Version::currentLive();
                if ($liveVersion instanceof Version) {
                    $workspace->base_version_id = $liveVersion->id;
                }
            }
        });
    }

    protected function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', WorkspaceStatusEnum::Open->value);
    }

    protected function scopeInReview(Builder $query): Builder
    {
        return $query->where('status', WorkspaceStatusEnum::InReview->value);
    }

    protected function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', WorkspaceStatusEnum::Approved->value);
    }

    protected function scopePublished(Builder $query): Builder
    {
        return $query->where('status', WorkspaceStatusEnum::Published->value);
    }

    protected function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WorkspaceStatusEnum::Open->value,
            WorkspaceStatusEnum::InReview->value,
            WorkspaceStatusEnum::Approved->value,
        ]);
    }

    protected function casts(): array
    {
        return [
            'status' => WorkspaceStatusEnum::class,
            'kind' => WorkspaceKindEnum::class,
            'settings' => WorkspaceSettingsData::class,
            'submitted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'publish_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }
}
