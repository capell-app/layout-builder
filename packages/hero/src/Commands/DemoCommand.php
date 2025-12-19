<?php

declare(strict_types=1);

namespace Capell\Hero\Commands;

use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Core\Commands\Concerns\HasSitesOption;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Hero\Actions\AddHeroToLayoutAction;
use Capell\Hero\Actions\CreateHeroContentTypeAction;
use Capell\Hero\Actions\CreateHeroWidgetAction;
use Capell\Layout\Services\Creator\DemoCreator;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    use HasSitesOption;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts demo hero content into the selected site(s).';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell-hero:demo {--sites=}';

    private DemoCreator $demoCreator;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('sites')) {
            $siteOptions = is_string($this->option('sites'))
                ? explode(',', $this->option('sites'))
                : (is_array($this->option('sites')) ? $this->option('sites') : null);
        } else {
            $siteOptions = $this->getSelectedSites();
        }

        $sites = CapellCore::getModel(ModelEnum::Site)::query()->with(['language', 'languages'])->whereIn('name', $siteOptions)->get();

        if ($sites->isEmpty()) {
            $this->error('Unable to find any sites for: ' . implode(', ', (array) $siteOptions));

            return Command::FAILURE;
        }

        $this->demoCreator = resolve(DemoCreator::class);

        $heroWidget = CreateHeroWidgetAction::run();

        foreach ($sites as $site) {
            $this->newLine();
            $this->line(sprintf('Selected site: %s', $site->name));

            $homepage = CapellCore::getModel(ModelEnum::Page)::getSiteHomePage($site);

            $homepage->loadMissing('layout');

            AddHeroToLayoutAction::run($homepage->layout);

            $type = CreateHeroContentTypeAction::run();

            $this->demoCreator->createContentsWidget($heroWidget, $homepage, container: 'hero', type: $type);

            if (CapellCore::hasPackage('capell-blog')) {
                $blogPage = CapellCore::getModel(ModelEnum::Page)::query()
                    ->with('translations')
                    ->where('site_id', $site->id)
                    ->whereRelation('type', 'key', BlogPageTypeEnum::Blog->value)
                    ->first();

                if ($blogPage instanceof Page) {
                    foreach ($blogPage->translations as $translation) {
                        $meta = $translation->meta;
                        $meta['hero'] = '<h1>' . __('capell-blog::generic.latest_articles') . '</h1><p>' . __('capell-blog::generic.blog_intro') . '</p>';

                        $translation->update(['meta' => $meta]);
                    }
                }

                if (CapellCore::hasPackage('capell-blog')) {
                    $this->addHeroToArticlePages($site);
                }
            }

            $this->line('Demo hero content has been successfully created for site: ' . $site->name);
        }

        $this->line('Hero demo content inserted successfully.');

        return Command::SUCCESS;
    }

    private function addHeroToArticlePages(Site $site): void
    {
        $articlePages = CapellCore::getModel(ModelEnum::Page)::query()
            ->with('translations')
            ->where('site_id', $site->id)
            ->whereRelation('type', 'key', 'article')
            ->get();

        $articlePages->each(function (Page $page): void {
            foreach ($page->translations as $translation) {
                $meta = $translation->meta;
                $meta['hero'] = '<h1>' . $translation->title . '</h1>';

                $translation->update(['meta' => $meta]);
            }
        });
    }
}
