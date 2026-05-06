<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands;

use Capell\Core\Actions\Makers\RunMakerAction;
use Capell\Core\Data\Makers\MakerInputData;
use Illuminate\Console\Command;
use Throwable;

class MakeWidgetCommand extends Command
{
    protected $signature = 'capell:layout-builder-make-widget
        {name : The widget name (e.g. HeroBanner)}
        {--livewire : Also scaffold a Livewire widget class and view}
        {--F|force : Overwrite existing files after warning}';

    protected $description = 'Scaffold a LayoutBuilder widget Blade view and print the seeder snippet for its Type + Widget rows';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        try {
            $result = RunMakerAction::run(new MakerInputData(
                maker: 'layout-builder.widget',
                values: ['name' => $name, 'livewire' => (bool) $this->option('livewire')],
                dryRun: false,
                force: (bool) $this->option('force'),
                databaseWrites: false,
            ));
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }

        foreach ($result->files as $file) {
            $this->info(sprintf('%s: %s', $file->operation, $file->path));
        }

        $this->newLine();

        foreach ($result->notes as $note) {
            $this->line($note);
        }

        return self::SUCCESS;
    }
}
