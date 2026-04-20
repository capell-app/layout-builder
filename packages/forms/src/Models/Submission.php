<?php

declare(strict_types=1);

namespace Capell\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    /** @var array<string> */
    protected $fillable = ['form_id', 'data', 'submitted_at'];

    /** @var array<string, string> */
    protected $casts = [
        'data' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
