<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Console\Command;

class FakerCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'capell:layout-builder-faker {--count=25} {--force}';

    /**
     * @var string
     */
    protected $description = 'Seed fake layout-builder layouts and widgets.';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        if ($count < 1) {
            $this->error('The --count option must be at least 1.');

            return Command::FAILURE;
        }

        Layout::factory()->count($count)->create();
        Widget::factory()->count($count)->create();

        $this->info(sprintf('Seeded %d layouts and %d widgets.', $count, $count));

        return Command::SUCCESS;
    }
}
