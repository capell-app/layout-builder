<?php

declare(strict_types=1);

namespace Capell\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot model representing rows in the `taggables` table.
 *
 * @property int $tag_id
 * @property int $taggable_id
 * @property string $taggable_type
 */
class Taggable extends Model
{
    use HasFactory;

    /**
     * This pivot table does not have timestamps by default.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'taggables';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
    ];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id', 'id');
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo('taggable');
    }
}
