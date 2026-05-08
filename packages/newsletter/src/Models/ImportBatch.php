<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Core\Models\Site;
use Capell\Newsletter\Enums\ImportBatchStatus;
use Capell\Newsletter\Enums\ImportBatchType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImportBatch extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'type',
        'status',
        'filename',
        'consent_basis',
        'dry_run_payload',
        'source_meta',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'actor_type',
        'actor_id',
    ];

    protected $table = 'newsletter_import_batches';

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ImportBatchType::class,
            'status' => ImportBatchStatus::class,
            'dry_run_payload' => 'encrypted:array',
            'source_meta' => 'encrypted:array',
        ];
    }
}
