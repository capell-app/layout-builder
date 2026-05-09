<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailTrackingTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTrackingToken extends Model
{
    /** @use HasFactory<EmailTrackingTokenFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'email_recipient_id',
        'token_hash',
        'type',
        'destination_url',
        'expires_at',
        'consumed_at',
    ];

    protected static string $factory = EmailTrackingTokenFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.tracking_tokens');

        return is_string($tableName) ? $tableName : 'email_tracking_tokens';
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
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }
}
