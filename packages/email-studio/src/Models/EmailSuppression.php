<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Models;

use Capell\EmailStudio\Database\Factories\EmailSuppressionFactory;
use Capell\EmailStudio\Enums\SuppressionReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSuppression extends Model
{
    /** @use HasFactory<EmailSuppressionFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'site_id',
        'site_scope_key',
        'email',
        'normalized_email',
        'email_hash',
        'reason',
        'source',
        'notes',
        'suppressed_at',
        'released_at',
    ];

    protected static string $factory = EmailSuppressionFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-email-studio.tables.suppressions');

        return is_string($tableName) ? $tableName : 'email_suppressions';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => SuppressionReason::class,
            'suppressed_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }
}
