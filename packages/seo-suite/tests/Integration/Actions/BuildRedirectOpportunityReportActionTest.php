<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\SeoSuite\Actions\BuildRedirectOpportunityReportAction;
use Capell\SeoSuite\Data\RedirectOpportunityData;
use Capell\SeoSuite\Models\BrokenLink;

it('groups repeated broken targets into redirect opportunities', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $firstPage = PageFactory::new()->site($site)->withTranslations($language)->create(['name' => 'First broken page']);
    $secondPage = PageFactory::new()->site($site)->withTranslations($language)->create(['name' => 'Second broken page']);

    PageUrl::factory()->page($firstPage)->site($site)->language($language)->state(['url' => '/first-page'])->create();
    PageUrl::factory()->page($secondPage)->site($site)->language($language)->state(['url' => '/second-page'])->create();

    BrokenLink::query()->create([
        'page_id' => $firstPage->id,
        'target_url' => '/missing-page',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);
    BrokenLink::query()->create([
        'page_id' => $secondPage->id,
        'target_url' => '/missing-page',
        'http_status' => 410,
        'last_checked_at' => now(),
    ]);

    $opportunities = BuildRedirectOpportunityReportAction::run(siteId: $site->id, languageId: $language->id);

    expect($opportunities)->toHaveCount(1)
        ->and($opportunities[0])->toBeInstanceOf(RedirectOpportunityData::class)
        ->and($opportunities[0]->sourceUrl)->toBe('/missing-page')
        ->and($opportunities[0]->hits)->toBe(2)
        ->and($opportunities[0]->siteId)->toBe($site->id)
        ->and($opportunities[0]->languageId)->toBe($language->id)
        ->and($opportunities[0]->suggestedTargetUrl)->toBeNull()
        ->and($opportunities[0]->pageName)->toBe('First broken page');
});

it('suggests an existing non redirect page url for a direct match', function (): void {
    $language = LanguageFactory::new()->create(['name' => 'English', 'code' => 'en']);
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $page = PageFactory::new()->site($site)->withTranslations($language)->create();
    $targetPage = PageFactory::new()->site($site)->withTranslations($language)->create();

    PageUrl::factory()->page($page)->site($site)->language($language)->state(['url' => '/source-page'])->create();
    PageUrl::factory()
        ->page($targetPage)
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/existing-page',
            'type' => null,
        ])
        ->create();
    PageUrl::factory()
        ->manualRedirect()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/redirect-page',
            'target_url' => '/existing-page',
            'type' => UrlTypeEnum::Redirect,
        ])
        ->create();

    BrokenLink::query()->create([
        'page_id' => $page->id,
        'target_url' => '/existing-page',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);

    $opportunities = BuildRedirectOpportunityReportAction::run(siteId: $site->id, languageId: $language->id);

    expect($opportunities)->toHaveCount(1)
        ->and($opportunities[0]->suggestedTargetUrl)->toBe('/existing-page');
});
