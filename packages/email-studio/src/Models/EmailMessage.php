<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailMessageFactory;
use Capell\EmailStudio\Enums\EmailMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    /** @use HasFactory<EmailMessageFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'email_profile_id',
        'email_template_id',
        'email_template_variant_id',
        'status',
        'subject',
        'preview_text',
        'rendered_html',
        'rendered_text',
        'context_snapshot',
        'headers',
        'triggered_by_type',
        'triggered_by_id',
        'queued_at',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected static string $factory = EmailMessageFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.messages');

        return is_string($tableName) ? $tableName : 'email_messages';
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmailProfile::class, 'email_profile_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function templateVariant(): BelongsTo
    {
        return $this->belongsTo(EmailTemplateVariant::class, 'email_template_variant_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailRecipient::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(EmailReply::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmailMessageStatus::class,
            'context_snapshot' => 'array',
            'headers' => 'array',
            'queued_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }
}
