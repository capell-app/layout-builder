<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailReplyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailReply extends Model
{
    /** @use HasFactory<EmailReplyFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'email_message_id',
        'email_recipient_id',
        'provider_message_id',
        'from_email',
        'normalized_from_email',
        'from_email_hash',
        'from_name',
        'subject',
        'body',
        'provider_payload',
        'received_at',
    ];

    protected static string $factory = EmailReplyFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.replies');

        return is_string($tableName) ? $tableName : 'email_replies';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'email_message_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(EmailRecipient::class, 'email_recipient_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider_payload' => 'array',
            'received_at' => 'immutable_datetime',
        ];
    }
}
