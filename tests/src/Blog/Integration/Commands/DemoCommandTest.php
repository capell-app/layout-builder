<?php

declare(strict_types=1);

use Capell\Blog\Enums\ModelEnum as BlogModelEnum;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

it('runs demo command and creates articles and tags for the site', function (): void {
    /** @var Language $language */
    $language = Language::factory()->create([
        'code' => 'en',
    ]);

    /** @var Site $site */
    $site = Site::factory()
        ->recycle($language)
        ->withTranslations()
        ->create();

    // Ensure the site has language relations loaded
    $site->refresh();
    $site->loadMissing('languages', 'language');

    // Run the demo command for this site with a small limit
    $this->artisan('capell:blog-demo', [
        '--sites' => $site->name,
        '--limit' => 2,
    ])
        ->expectsOutput('Setting up demo blog for site: ' . $site->name)
        ->expectsOutput('Blog demo setup completed for selected sites.')
        ->assertExitCode(Command::SUCCESS);

    // Verify that article pages were created under the blog page for the site
    $pageModel = CapellCore::getModel(CoreModelEnum::Page);

    /** @var Collection<int, Page> $articles */
    $articles = $pageModel::query()
        ->where('site_id', $site->id)
        ->whereRelation('type', 'key', 'article')
        ->get();

    expect($articles->count())->toBeGreaterThanOrEqual(1);

    // Verify that tags were created for pages
    $tagModel = CapellCore::getModel(BlogModelEnum::Tag);
    $tagsCount = $tagModel::query()->count();
    expect($tagsCount)->toBeGreaterThanOrEqual(1);

    // Verify that at least one created article has tags associated
    $articleWithTags = $articles->first(fn (Page $page): bool => $page->tags()->exists());

    expect($articleWithTags)->not()->toBeNull();
});
