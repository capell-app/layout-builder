<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Core\Models\Site;
use Capell\Newsletter\Enums\SegmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'name',
        'handle',
        'type',
        'filters',
        'is_active',
    ];

    protected $table = 'newsletter_segments';

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(
            Subscriber::class,
            'newsletter_segment_subscriber',
            'newsletter_segment_id',
            'newsletter_subscriber_id',
        )->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SegmentType::class,
            'filters' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
