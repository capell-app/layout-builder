<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Models;

use Capell\Core\Models\Site;
use Capell\FormBuilder\Data\FormFieldData;
use Capell\FormBuilder\Data\FormSettingsData;
use Capell\FormBuilder\Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\LaravelData\DataCollection;

/**
 * @property DataCollection<int, FormFieldData>|null $schema
 */
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'name',
        'handle',
        'description',
        'schema',
        'settings',
        'is_active',
    ];

    protected static string $factory = FormFactory::class;

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema' => DataCollection::class . ':' . FormFieldData::class,
            'settings' => FormSettingsData::class,
            'is_active' => 'boolean',
        ];
    }
}
