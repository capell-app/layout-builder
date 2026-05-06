<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Console\Commands;

use Capell\SeoSuite\Support\PrismProvider;
use Illuminate\Console\Command;

class TestOpenAiConnectionCommand extends Command
{
    protected $signature = 'capell:admin-test-openai';

    protected $description = 'Test connectivity to OpenAI';

    public function handle(PrismProvider $provider): int
    {
        $healthy = $provider->isAvailable();
        $this->info($healthy ? 'OpenAI API reachable.' : 'OpenAI API unreachable.');

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
