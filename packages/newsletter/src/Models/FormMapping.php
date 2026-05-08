<?php

declare(strict_types=1);

namespace Capell\Newsletter\Models;

use Capell\Core\Models\Site;
use Capell\Newsletter\Enums\ConfirmationMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, int|string>|null $fixed_tag_ids
 * @property array<string, array<string, int|string>>|null $field_tag_mappings
 */
class FormMapping extends Model
{
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'form_id',
        'name',
        'form_handle',
        'email_field',
        'first_name_field',
        'last_name_field',
        'consent_field',
        'consent_text',
        'consent_version',
        'fixed_tag_ids',
        'field_tag_mappings',
        'requires_double_opt_in',
        'confirmation_mode',
        'is_active',
    ];

    protected $table = 'newsletter_form_mappings';

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo($this->formModelClass());
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
            'fixed_tag_ids' => 'array',
            'field_tag_mappings' => 'array',
            'requires_double_opt_in' => 'boolean',
            'confirmation_mode' => ConfirmationMode::class,
            'is_active' => 'boolean',
        ];
    }

    private function formModelClass(): string
    {
        return implode('\\', ['Capell', 'FormBuilder', 'Models', 'Form']);
    }
}
