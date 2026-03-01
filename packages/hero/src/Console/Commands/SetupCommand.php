<?php

declare(strict_types=1);

namespace Capell\Hero\Console\Commands;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setting up hero package';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell:hero-setup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var class-string<Layout> $layoutModel */
        $layoutModel = CapellCore::getModel(ModelEnum::Layout);

        $layoutModel::query()->whereNotIn('key', [
            LayoutEnum::Default->value,
            LayoutEnum::Results->value,
        ])
            ->each(function (Layout $layout): void {
                AddHeroToLayoutAction::run($layout);
            });

        $this->newLine();
        $this->info('Capell Hero setup successfully.');

        return Command::SUCCESS;
    }
}
