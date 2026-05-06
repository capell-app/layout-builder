<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Console\Commands\Hero;

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AddHeroWidgetToLayoutAction;
use Capell\LayoutBuilder\Actions\CreateHeroWidgetAction;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $description = 'Setting up hero widgets';

    protected $signature = 'capell:hero-setup';

    public function handle(): int
    {
        /** @var class-string<Layout> $layoutModel */
        $layoutModel = Layout::class;

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
