<?php

declare(strict_types=1);

namespace Capell\Mosaic\Console\Commands;

use Capell\Mosaic\Actions\MakeWidgetAction;
use Illuminate\Console\Command;

class MakeWidgetCommand extends Command
{
    protected $signature = 'capell:mosaic-make-widget {name : The widget name (e.g. HeroBanner)}';

    protected $description = 'Scaffold a Mosaic widget Blade view and print the seeder snippet for its Type + Widget rows';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        $result = MakeWidgetAction::run($name);

        if ($result->created) {
            $this->info(sprintf('Blade view created at %s', $result->viewPath));
        } else {
            $this->warn(sprintf('Blade view already exists at %s — left untouched.', $result->viewPath));
        }

        $this->newLine();
        $this->line('Paste the following into a seeder or migration to register the widget:');
        $this->newLine();
        $this->line($result->seederSnippet);
        $this->newLine();

        return self::SUCCESS;
    }
}
