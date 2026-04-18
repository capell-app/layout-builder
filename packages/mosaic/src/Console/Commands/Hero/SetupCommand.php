<?php

declare(strict_types=1);

namespace Capell\Mosaic\Console\Commands\Hero;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Mosaic\Actions\AddHeroWidgetToLayoutAction;
use Capell\Mosaic\Actions\CreateHeroWidgetAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Setting up hero widgets';

    protected $signature = 'capell:hero-setup';

    public function handle(): int
    {
        /** @var class-string<Layout> $layoutModel */
        $layoutModel = CapellCore::getModel(ModelEnum::Layout);

        $heroWidget = CreateHeroWidgetAction::run();

        $heroBannerWidget = CreateHeroWidgetAction::run(key: 'hero-banner', height: 'large');

        $layout = $layoutModel::query()->where('key', LayoutEnum::Home->value)->first();
        if ($layout instanceof Layout) {
            AddHeroWidgetToLayoutAction::run($heroBannerWidget, $layout);
        }

        $layoutModel::query()->whereIn('key', [
            LayoutEnum::Default->value,
            LayoutEnum::Results->value,
        ])
            ->each(function (Layout $layout) use ($heroWidget): void {
                AddHeroWidgetToLayoutAction::run($heroWidget, $layout);
            });

        $this->newLine();
        $this->info('Capell Hero setup successfully.');

        return Command::SUCCESS;
    }
}
