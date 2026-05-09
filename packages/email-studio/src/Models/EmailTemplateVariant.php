<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailTemplateVariantFactory;
use Capell\EmailStudio\Enums\EmailVariantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplateVariant extends Model
{
    /** @use HasFactory<EmailTemplateVariantFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'email_template_id',
        'email_profile_id',
        'locale',
        'status',
        'version',
        'subject',
        'preview_text',
        'html_body',
        'text_body',
        'approved_at',
        'approved_by',
    ];

    protected static string $factory = EmailTemplateVariantFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.template_variants');

        return is_string($tableName) ? $tableName : 'email_template_variants';
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmailProfile::class, 'email_profile_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmailVariantStatus::class,
            'approved_at' => 'immutable_datetime',
        ];
    }
}
