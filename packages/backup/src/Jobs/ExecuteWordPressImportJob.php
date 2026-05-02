<?php

declare(strict_types=1);

namespace Capell\Backup\Jobs;

use Capell\Backup\Exceptions\NotImplementedException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * H4 placeholder. Will run the WordPress import execute phase on the
 * backup queue, mirroring ExecuteImportPlanJob.
 */
final class ExecuteWordPressImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $importSessionId) {}

    public function handle(): never
    {
        throw NotImplementedException::forPhase('H4', self::class);
    }
}
