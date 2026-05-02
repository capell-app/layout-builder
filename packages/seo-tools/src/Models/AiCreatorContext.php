<?php

declare(strict_types=1);

namespace Capell\SeoTools\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCreatorContext extends Model
{
    use HasFactory;

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
