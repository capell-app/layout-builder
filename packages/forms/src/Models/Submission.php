<?php

declare(strict_types=1);

namespace Capell\Forms\Models;

use Capell\Core\Models\Site;
use Capell\Forms\Casts\EncryptedDataCast;
use Capell\Forms\Data\SubmissionMetaData;
use Capell\Forms\Data\SubmissionPayloadData;
use Capell\Forms\Database\Factories\SubmissionFactory;
use Capell\Forms\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'form_id',
        'site_id',
        'payload',
        'meta',
        'status',
        'submitted_at',
    ];

    protected static string $factory = SubmissionFactory::class;

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => EncryptedDataCast::class . ':' . SubmissionPayloadData::class,
            'meta' => EncryptedDataCast::class . ':' . SubmissionMetaData::class,
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }
}
