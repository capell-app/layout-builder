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
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageWorkflowState;
use Capell\Core\Models\Site;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\WithdrawPublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\Models\PublicWidgetSnapshot;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotWorkflowSubscriber;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\PublishingStudio\Facades\CapellPublishingStudio;
use Illuminate\Support\Facades\DB;

it('registers the widget snapshot subscriber only with the core lifecycle manager', function (): void {
    expect(CapellCore::subscriberManager()->hasSubscriber(WidgetSnapshotWorkflowSubscriber::class))->toBeTrue()
        ->and(CapellPublishingStudio::getSubscribers())->not->toContain(WidgetSnapshotWorkflowSubscriber::class);
});

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
    $page = $context->page;
    if (! $page instanceof Page) {
        throw new RuntimeException('Expected a page render context.');
    }
    PageSavedAction::run($page);
    $page->forceFill(['visible_until' => now()->subMinute()])->saveQuietly();

    PageSavedAction::run($context->page);

    expect(PublicWidgetSnapshot::query()->whereNotNull('revoked_at')->count())->toBe(1);
});

it('treats an explicit unpublished workflow state as non-public even with null visibility dates', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Workflow');
    $page = $context->page;
    if (! $page instanceof Page) {
        throw new RuntimeException('Expected a page render context.');
    }
    PageSavedAction::run($page);
    PageWorkflowState::query()->where('page_uuid', $page->uuid)->update([
        'status' => PageWorkflowStatus::Unpublished,
        'aggregate_version' => 2,
    ]);

    PageSavedAction::run($context->page);

    $snapshot = PublicWidgetSnapshot::query()->sole();
    expect($snapshot->revoked_at)->not->toBeNull()
        ->and($snapshot->current_key)->toBeNull();
});

it('revokes through the workflow subscriber for unpublish and archive stored events', function (string $eventName, string $eventClass): void {
    expect(CapellCore::subscriberManager()->hasSubscriber(WidgetSnapshotWorkflowSubscriber::class))->toBeTrue();
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Workflow event');
    $page = $context->page;
    if (! $page instanceof Page) {
        throw new RuntimeException('Expected a page render context.');
    }
    PageSavedAction::run($page);
    $event = new $eventClass;
    if (! method_exists($event, 'setAggregateRootUuid')) {
        throw new RuntimeException('Expected a workflow aggregate event.');
    }
    $event = $event->setAggregateRootUuid($page->uuid);

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

it('rebuilds snapshots for every page using a changed shared layout after commit', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $first = snapshotListenerContext('First page');
    $second = snapshotListenerContext('Second page');
    $firstPage = $first->page;
    $secondPage = $second->page;
    throw_unless($firstPage instanceof Page && $secondPage instanceof Page, RuntimeException::class, 'Expected page contexts.');
    $layout = $first->layout;
    throw_unless($layout instanceof Layout, RuntimeException::class, 'Expected a layout context.');
    $secondPage->forceFill(['layout_id' => $layout->getKey()])->saveQuietly();
    PageSavedAction::run($first->page);
    PageSavedAction::run($second->page);

    $layout->update(['containers' => ['main' => ['widgets' => [[
        'type' => 'capell-app.slideshow',
        'data' => ['title' => 'Shared changed', '__capell' => ['instance_id' => 'shared-target', 'state_version' => 2]],
    ]]]]]);

    $snapshots = PublicWidgetSnapshot::query()->where('target_instance_id', 'shared-target')->whereNotNull('current_key')->get();
    expect($snapshots)->toHaveCount(2)
        ->and($snapshots->pluck('pageable_id')->all())->toEqualCanonicalizing([$firstPage->getKey(), $secondPage->getKey()])
        ->and($snapshots->map(fn (PublicWidgetSnapshot $snapshot): mixed => data_get($snapshot->encrypted_payload, 'widget.data.title'))->all())
        ->toBe(['Shared changed', 'Shared changed']);
});

it('does not publish shared layout snapshots before commit or after rollback', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    $context = snapshotListenerContext('Rollback');
    $layout = $context->layout;
    throw_unless($layout instanceof Layout, RuntimeException::class, 'Expected a layout context.');
    DB::beginTransaction();
    try {
        $layout->update(['containers' => ['main' => ['widgets' => [[
            'type' => 'capell-app.slideshow',
            'data' => ['title' => 'Uncommitted', '__capell' => ['instance_id' => 'rollback-target', 'state_version' => 2]],
        ]]]]]);
        expect(PublicWidgetSnapshot::query()->where('target_instance_id', 'rollback-target')->exists())->toBeFalse();
    } finally {
        DB::rollBack();
    }
    expect(PublicWidgetSnapshot::query()->where('target_instance_id', 'rollback-target')->exists())->toBeFalse();
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
