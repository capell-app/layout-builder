<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FailedJob extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function getTable(): string
    {
        return config('queue.failed.table', 'failed_jobs');
    }
}
