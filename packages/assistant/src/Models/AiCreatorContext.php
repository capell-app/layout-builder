<?php

declare(strict_types=1);

namespace Capell\Assistant\Models;

use Illuminate\Database\Eloquent\Model;

class AiCreatorContext extends Model
{
    protected $fillable = [
        'site_id',
        'tone',
        'industry',
        'brand_voice_notes',
        'target_audience',
    ];

    protected $casts = [
        'site_id' => 'integer',
    ];
}
