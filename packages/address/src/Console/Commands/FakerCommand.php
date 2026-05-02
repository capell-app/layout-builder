<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Models\Address;
use Illuminate\Console\Command;

class FakerCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'capell:address-faker {--count=25} {--force}';

    /**
     * @var string
     */
    protected $description = 'Seed fake addresses (and their countries).';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        if ($count < 1) {
            $this->error('The --count option must be at least 1.');

            return Command::FAILURE;
        }

        Address::factory()->count($count)->create();

        $this->info(sprintf('Seeded %d fake addresses.', $count));

        return Command::SUCCESS;
    }
}
