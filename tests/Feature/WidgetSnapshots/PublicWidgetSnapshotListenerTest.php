<?php

declare(strict_types=1);

use Capell\Core\Actions\PageDeletedAction;
use Capell\Core\Actions\PageSavedAction;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Enums\ListenerEnum;
use Capell\Core\EventSourcing\Enums\PageWorkflowStatus;
use Capell\Core\EventSourcing\Events\PageArchived;
use Capell\Core\EventSourcing\Events\PageUnpublished;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageWorkflowState;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\WithdrawPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotWorkflowSubscriber;
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

it('treats an explicit unpublished workflow state as non-public even with null visibility dates', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Workflow');
    PageSavedAction::run($context->page);
    PageWorkflowState::query()->where('page_uuid', $context->page->uuid)->update([
        'status' => PageWorkflowStatus::Unpublished,
        'aggregate_version' => 2,
    ]);

    PageSavedAction::run($context->page);

    expect(PublicWidgetSnapshot::query()->sole()->revoked_at)->not->toBeNull();
});

it('revokes through the workflow subscriber for unpublish and archive stored events', function (string $eventName, string $eventClass): void {
    expect(CapellCore::subscriberManager()->hasSubscriber(WidgetSnapshotWorkflowSubscriber::class))->toBeTrue();
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Workflow event');
    PageSavedAction::run($context->page);
    $event = (new $eventClass)->setAggregateRootUuid($context->page->uuid);

    resolve(WidgetSnapshotWorkflowSubscriber::class)->handle($eventName, $event);

    expect(PublicWidgetSnapshot::query()->sole()->revoked_at)->not->toBeNull();
})->with([
    'unpublish' => [ListenerEnum::PageUnpublished->value, PageUnpublished::class],
    'archive' => [ListenerEnum::PageArchived->value, PageArchived::class],
]);

it('supports immediate explicit security withdrawal and lets revocation win over grace', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Withdraw');
    PageSavedAction::run($context->page);
    PublicWidgetSnapshot::query()->update(['superseded_at' => now()]);

    WithdrawPublicWidgetSnapshotsAction::run($context->page);

    expect(PublicWidgetSnapshot::query()->sole()->revoked_at)->not->toBeNull();
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
    PageWorkflowState::query()->updateOrCreate(
        ['page_uuid' => $page->uuid],
        ['status' => PageWorkflowStatus::Published, 'aggregate_version' => 1],
    );

    return new FrontendRenderContextData($page, $site, $language, $page->layout, $site->theme);
}
