<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailRecipientFactory;
use Capell\EmailStudio\Enums\EmailRecipientStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property EmailRecipientStatus $status
 * @property string|null $provider_message_id
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $suppressed_at
 * @property string|null $failure_reason
 */
class EmailRecipient extends Model
{
    /** @use HasFactory<EmailRecipientFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'email_message_id',
        'type',
        'email',
        'normalized_email',
        'email_hash',
        'name',
        'status',
        'provider_message_id',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'complained_at',
        'replied_at',
        'suppressed_at',
        'failure_reason',
    ];

    protected static string $factory = EmailRecipientFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.recipients');

        return is_string($tableName) ? $tableName : 'email_recipients';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'email_message_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(EmailReply::class);
    }

    public function trackingTokens(): HasMany
    {
        return $this->hasMany(EmailTrackingToken::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmailRecipientStatus::class,
            'sent_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'opened_at' => 'immutable_datetime',
            'clicked_at' => 'immutable_datetime',
            'bounced_at' => 'immutable_datetime',
            'complained_at' => 'immutable_datetime',
            'replied_at' => 'immutable_datetime',
            'suppressed_at' => 'immutable_datetime',
        ];
    }
}
