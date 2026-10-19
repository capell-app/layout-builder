<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Actions\WidgetExtensions\RestoreWidgetInteractionContextAction;

it('restores lazy interaction context after the Frontend facade has resolved', function (): void {
    $previous = new FrontendContext(site: new Site, language: null, page: null, layout: null, theme: null, params: [], slug: null);
    Frontend::swap($previous);
    expect(Frontend::site())->toBe($previous->site());
    $language = Language::factory()->createOne(['code' => 'cy']);
    $site = Site::factory()->createOne(['language_id' => $language->id]);
    $page = Page::factory()->site($site)->withTranslations($language)->createOne();
    $restored = RestoreWidgetInteractionContextAction::run([
        'version' => 1,
        'purpose' => 'widget-interaction',
        'site_id' => $site->getKey(),
        'page_id' => $page->getKey(),
        'page_type' => $page->getMorphClass(),
        'language_id' => $language->getKey(),
        'layout_id' => $page->layout?->getKey(),
        'theme_id' => $site->theme?->getKey(),
    ]);

    expect($restored)->not->toBeNull()
        ->and(resolve(FrontendContextReader::class)->site()?->getKey())->toBe($site->getKey())
        ->and(Frontend::site()?->getKey())->toBe($site->getKey())
        ->and(Frontend::getFacadeRoot())->toBe(resolve(FrontendContextReader::class));
});
