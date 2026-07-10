<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature;

use Capell\Core\Events\PageDeleted;
use Capell\Core\Events\PageSaved;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Subscriber\SubscriberManager;
use Capell\LayoutBuilder\Actions\WidgetSnapshots\PrunePublicWidgetSnapshotsAction;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Listeners\MaintainPublicWidgetSnapshotsListener;
use Capell\LayoutBuilder\Support\WidgetSnapshots\WidgetSnapshotWorkflowSubscriber;
use Capell\Tests\AbstractTestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Livewire\LivewireServiceProvider;
use Override;

final class LayoutBuilderUninstalledProviderTest extends AbstractTestCase
{
    public function test_it_does_not_register_page_widget_assets_as_cloneable_when_uninstalled(): void
    {
        expect(CapellCore::getCloneableRelations('page'))->not->toContain('widgetAssets');
    }

    public function test_it_registers_no_snapshot_lifecycle_hooks_or_schedule_when_uninstalled(): void
    {
        /** @var Dispatcher $events */
        $events = app('events');
        $rawListeners = $events->getRawListeners();
        $snapshotListeners = collect([
            ...($rawListeners[PageSaved::class] ?? []),
            ...($rawListeners[PageDeleted::class] ?? []),
        ])->filter(fn (mixed $listener): bool => is_array($listener)
            && ($listener[0] ?? null) === MaintainPublicWidgetSnapshotsListener::class);
        $scheduled = collect(resolve(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'capell:widget-snapshots:prune'));
        $snapshotQueries = [];
        DB::listen(function ($query) use (&$snapshotQueries): void {
            if (str_contains($query->sql, 'public_widget_snapshots')) {
                $snapshotQueries[] = $query->sql;
            }
        });

        expect($snapshotListeners)->toBeEmpty()
            ->and(resolve(SubscriberManager::class)->hasSubscriber(WidgetSnapshotWorkflowSubscriber::class))->toBeFalse()
            ->and($scheduled)->toBeEmpty()
            ->and(PrunePublicWidgetSnapshotsAction::run())->toBe(0)
            ->and($snapshotQueries)->toBeEmpty();
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-layout-builder-uninstalled';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutBuilderServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(LayoutBuilderServiceProvider::$packageName, false);
    }
}
