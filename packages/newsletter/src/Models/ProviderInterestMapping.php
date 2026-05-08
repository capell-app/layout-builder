<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Tags\Models\Tag;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderInterestMapping extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'provider_audience_id',
        'tag_id',
        'remote_interest_id',
        'remote_interest_type',
        'remote_name',
    ];

    protected $table = 'newsletter_provider_interest_mappings';

    public function providerAudience(): BelongsTo
    {
        return $this->belongsTo(ProviderAudience::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
