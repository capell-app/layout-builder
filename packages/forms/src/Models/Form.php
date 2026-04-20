<?php

declare(strict_types=1);

namespace Capell\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    /** @var array<string> */
    protected $fillable = ['name', 'description', 'site_id'];

    /** @var array<string, string> */
    protected $casts = [];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
