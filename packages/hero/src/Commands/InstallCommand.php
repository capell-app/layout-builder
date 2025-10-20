<?php

declare(strict_types=1);

namespace Capell\Hero\Commands;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    use HasSitesOption;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts hero';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-hero:install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var class-string<Layout> $layoutModel */
        $layoutModel = CapellCore::getModel(ModelEnum::Layout);

        $layoutModel::query()->whereNotIn('key', [
            LayoutEnum::Default->value,
            LayoutEnum::Home->value,
            LayoutEnum::Results->value,
            LayoutEnum::Tags->value,
        ])
            ->each(function (Layout $layout): void {
                AddHeroToLayoutAction::run($layout);
            });

        $this->line('Hero package installed successfully.');

        return Command::SUCCESS;
    }
}
