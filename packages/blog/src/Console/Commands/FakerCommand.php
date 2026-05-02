<?php

declare(strict_types=1);

namespace Capell\Blog\Console\Commands;

use Capell\Blog\Models\Article;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Tags\Enums\TagTypeEnum;
use Capell\Tags\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FakerCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'capell:blog-faker {--count=25} {--sites=} {--languages=} {--force}';

    /**
     * @var string
     */
    protected $description = 'Seed fake blog articles and tags across sites.';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        if ($count < 1) {
            $this->error('The --count option must be at least 1.');

            return Command::FAILURE;
        }

        $sites = $this->resolveSites();

        if ($sites->isEmpty()) {
            $this->warn('No sites found. Skipping.');

            return Command::SUCCESS;
        }

        $languages = $this->resolveLanguages();
        $totalArticles = 0;

        $sites->each(function (Site $site) use ($count, $languages, &$totalArticles): void {
            Article::factory()
                ->count($count)
                ->site($site)
                ->withTranslations($languages)
                ->withTags()
                ->create();

            $totalArticles += $count;
            $this->info(sprintf('Seeded %d articles in site "%s".', $count, $site->name));
        });

        $tagCount = max(1, (int) floor($count / 5));
        Tag::factory()->count($tagCount)->create(['type' => TagTypeEnum::Page]);
        $this->info(sprintf('Seeded %d extra tags.', $tagCount));

        $this->info(sprintf('Total fake articles created: %d', $totalArticles));

        return Command::SUCCESS;
    }

    /**
     * @return Collection<int, Site>
     */
    private function resolveSites(): Collection
    {
        $names = $this->option('sites');

        if (is_string($names) && $names !== '') {
            $names = explode(',', $names);
        }

        if (is_array($names) && $names !== []) {
            return Site::query()->whereIn('name', $names)->get();
        }

        return Site::query()->get();
    }

    /**
     * @return Collection<int, Language>|null
     */
    private function resolveLanguages(): ?Collection
    {
        $codes = $this->option('languages');

        if (is_string($codes) && $codes !== '') {
            $codes = explode(',', $codes);
        }

        if (is_array($codes) && $codes !== []) {
            return Language::query()->whereIn('code', $codes)->get();
        }

        return null;
    }
}
