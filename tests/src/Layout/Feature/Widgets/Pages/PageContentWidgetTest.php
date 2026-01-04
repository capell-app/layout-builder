<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Frontend\Actions\RenderContentAction;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

it('creates page content widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);

    $widget = $creator->pageContentWidget();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('page-content')
        ->and($widget->meta['component'] ?? null)->toBe(WidgetComponentEnum::PageContent->value)
        ->and($widget->meta['page_content'] ?? null)->toBe(['title', 'content']);
});

it('renders page content widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pageContentWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    $paragraphs = array_filter(
        explode('</p>', str_replace('<p>', '', $page->translation->content)),
        static fn (string $paragraph): bool => trim($paragraph) !== '',
    );

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-page-content',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                'h1',
                fn (AssertElement $titleElm): BaseAssert => $titleElm->containsText($page->translation->title),
            )
                ->contains('p', count: count($paragraphs))
                ->each(
                    'p',
                    fn (AssertElement $titleElm, int $index): BaseAssert => $titleElm->containsText($paragraphs[$index]),
                ),
        );
});

it('renders page content widget on page blocks', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $creator = resolve(WidgetCreator::class);
    $widget = $creator->pageContentWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()
        ->site($site)
        ->layout($layout)
        ->for(Type::factory()->page()->default()->contentStructure(ContentStructure::Blocks))
        ->withTranslations(contentStructure: ContentStructure::Blocks)
        ->create();

    $context = [
        'page' => $page,
        'site' => $site,
        'theme' => $site->theme,
        'language' => $page->translation->language,
    ];

    $html = RenderContentAction::run(
        $page->translation->content,
        structure: ContentStructure::Blocks,
        context: $context,
        decodeEntities: true,
        asArray: true,
    );

    $paragraphs = array_values(array_map(
        static fn (array $element): ?string => $element['tag'] === 'p' ? $element['text'] : null,
        array_filter($html, static fn (array $element): bool => $element['tag'] === 'p'),
    ));

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists(
            '.widget-page-content',
            fn (AssertElement $elm): BaseAssert => $elm->find(
                'h1',
                fn (AssertElement $titleElm): BaseAssert => $titleElm->containsText($page->translation->title),
            )
                ->each(
                    'p',
                    fn (AssertElement $titleElm, int $index): BaseAssert => $titleElm->containsText($paragraphs[$index]),
                ),
        );
});
