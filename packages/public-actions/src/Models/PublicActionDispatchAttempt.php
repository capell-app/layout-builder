<?php

declare(strict_types=1);

namespace Capell\PublicActions\Models;

use Capell\PublicActions\Database\Factories\PublicActionDispatchAttemptFactory;
use Capell\PublicActions\Enums\PublicActionDispatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $attempt
 * @property string|null $error_message
 * @property string $request_hash
 * @property string|null $response_summary
 * @property int|null $response_status
 * @property PublicActionDispatchStatus $status
 */
class PublicActionDispatchAttempt extends Model
{
    /** @use HasFactory<PublicActionDispatchAttemptFactory> */
    use HasFactory;

    /** @var array<string> */
    protected $fillable = [
        'public_action_submission_id',
        'public_action_destination_id',
        'adapter',
        'status',
        'attempt',
        'request_hash',
        'response_status',
        'response_summary',
        'error_message',
        'dispatched_at',
    ];

    protected static string $factory = PublicActionDispatchAttemptFactory::class;

    public function getTable(): string
    {
        $tableName = config('capell-public-actions.tables.dispatch_attempts');

        return is_string($tableName) ? $tableName : 'public_action_dispatch_attempts';
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(PublicActionSubmission::class, 'public_action_submission_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(PublicActionDestination::class, 'public_action_destination_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublicActionDispatchStatus::class,
            'attempt' => 'integer',
            'response_status' => 'integer',
            'dispatched_at' => 'datetime',
        ];
    }
}
