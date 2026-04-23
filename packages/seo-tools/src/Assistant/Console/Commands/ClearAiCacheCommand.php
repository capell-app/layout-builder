<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearAiCacheCommand extends Command
{
    protected $signature = 'capell:admin-clear-ai-cache';

    protected $description = 'Clear AI generation caches';

    public function handle(): int
    {
        Cache::flush();
        $this->info('AI cache cleared.');

        return self::SUCCESS;
    }
}
