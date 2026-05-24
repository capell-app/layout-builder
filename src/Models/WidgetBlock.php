<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Statusable;
use Capell\Core\Models\Contracts\Userstampable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

class WidgetBlock extends Model implements Statusable, Userstampable
{
    use HasMetaData;

    /** @use HasStatus<self> */
    use HasStatus;

    use HasUserstamps;
    use SoftDeletes;

    protected $table = 'widget_blocks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'admin',
        'content',
        'key',
        'meta',
        'name',
        'order',
        'status',
        'type',
        'widget_id',
    ];

    /**
     * @return BelongsTo<Widget, $this>
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function scopeOrdered(Builder $query, string $dir = 'asc'): void
    {
        $query->orderBy($this->qualifyColumn('order'), $dir)
            ->orderBy($this->qualifyColumn('id'), $dir);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'admin' => 'json',
            'meta' => 'json',
            'order' => 'integer',
            'status' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
