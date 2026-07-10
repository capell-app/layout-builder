<?php

declare(strict_types=1);

use Capell\Core\Actions\PageDeletedAction;
use Capell\Core\Actions\PageSavedAction;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;

it('rebuilds public snapshots after save and immediately revokes every revision after delete', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Published');

    PageSavedAction::run($context->page);

    expect(PublicWidgetSnapshot::query()->count())->toBe(1)
        ->and(PublicWidgetSnapshot::query()->whereNull('revoked_at')->count())->toBe(1);

    PageDeletedAction::run($context->page);

    expect(PublicWidgetSnapshot::query()->whereNotNull('revoked_at')->count())->toBe(1);
});

it('revokes snapshots when a saved page is outside its public visibility window', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Scheduled');
    PageSavedAction::run($context->page);
    $context->page->forceFill(['visible_until' => now()->subMinute()])->saveQuietly();

    PageSavedAction::run($context->page);

    expect(PublicWidgetSnapshot::query()->whereNotNull('revoked_at')->count())->toBe(1);
});

function snapshotListenerContext(string $title): FrontendRenderContextData
{
    $language = Language::factory()->createOne(['code' => 'en']);
    $site = Site::factory()->createOne(['language_id' => $language->id]);
    $page = Page::factory()
        ->site($site)
        ->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations($language, ['content' => [[
            'type' => 'capell-app.slideshow',
            'data' => [
                'title' => $title,
                '__capell' => ['instance_id' => 'listener-target', 'state_version' => 2],
            ],
        ]]], contentStructure: ContentStructure::Blocks)
        ->createOne();
    $page->setRelation('translation', $page->translations()->firstOrFail());

    return new FrontendRenderContextData($page, $site, $language, $page->layout, $site->theme);
}
