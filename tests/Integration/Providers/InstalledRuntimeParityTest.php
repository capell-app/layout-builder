<?php

declare(strict_types=1);

use Capell\Core\Actions\InstallPackageAction;
use Capell\Core\Actions\RuntimeRefresh\RefreshInstalledPackageRuntimeAction;
use Capell\Core\Data\Runtime\RuntimeRoleSelectionData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Support\Runtime\RuntimeRoleResolver;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;

it('matches fresh installed boot after in-process installation and repeated refresh', function (): void {
    InstalledRuntimeParity::assertPackage('layout-builder');
});

it('keeps maintenance schedules available exactly once in every runtime role', function (string $role, bool $installed): void {
    InstalledRuntimeState::run(static function () use ($role, $installed): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('layout-builder'), $installed, static function (Application $app) use ($role): void {
            $app->instance(RuntimeRoleResolver::class, new RuntimeRoleResolver(RuntimeRoleSelectionData::fromConfiguredValue($role)));
        }, sourceRoot: dirname(__DIR__, 3));
        expect($app->make(RuntimeRoleResolver::class)->role()->value)->toBe($role);
        $schedule = $app->make(Schedule::class);
        $commands = ['capell:widget-snapshots:prune', 'capell:layout-builder:prune-bulk-change-runs'];
        foreach ($commands as $command) {
            expect(array_filter($schedule->events(), static fn (ScheduledEvent $event): bool => is_string($event->command) && str_contains($event->command, $command)))->toHaveCount($installed ? 1 : 0);
        }

        $package = CapellCore::getPackage('capell-app/layout-builder');
        if (! $installed) {
            InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
            InstallPackageAction::run($package);
        }

        RefreshInstalledPackageRuntimeAction::run($package);
        RefreshInstalledPackageRuntimeAction::run($package);

        foreach ($commands as $command) {
            expect(array_filter($schedule->events(), static fn (ScheduledEvent $event): bool => is_string($event->command) && str_contains($event->command, $command)))->toHaveCount(1);
        }
    });
})->with([
    'public fresh' => ['public', true],
    'public late' => ['public', false],
    'authoring fresh' => ['authoring', true],
    'authoring late' => ['authoring', false],
    'combined fresh' => ['combined', true],
    'combined late' => ['combined', false],
]);

it('retains presentation in a payload consumer built before installation', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('layout-builder'), false, sourceRoot: dirname(__DIR__, 3));
        $resolver = $app->make(PublicLayoutWidgetPayloadResolver::class);
        $widget = new Widget;
        $widget->key = 'content';
        $widget->setRelation('translation', null);

        $page = new Page;
        $language = new Language;
        $before = $resolver->data($widget, $page, $language, 'body', 0);
        expect($before)->toHaveKey('presentation');

        InstalledRuntimeTestCase::prepareLateInstallMigrations($app);
        $package = CapellCore::getPackage('capell-app/layout-builder');
        InstallPackageAction::run($package);
        RefreshInstalledPackageRuntimeAction::run($package);
        RefreshInstalledPackageRuntimeAction::run($package);

        expect($resolver->data($widget, $page, $language, 'body', 0))->toBe($before);
    });
});
