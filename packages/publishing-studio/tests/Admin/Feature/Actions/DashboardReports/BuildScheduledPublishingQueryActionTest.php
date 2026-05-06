<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\PublishingStudio\Actions\DashboardReports\BuildScheduledPublishingQueryAction;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Builder;

uses(CreatesAdminUser::class);

test('returns a Builder including pages with future visible_from or visible_until', function (): void {
    $pendingPublish = Page::factory()->create([
        'visible_from' => now()->addDays(3),
        'visible_until' => null,
    ]);
    $pendingUnpublish = Page::factory()->create([
        'visible_from' => now()->subMonth(),
        'visible_until' => now()->addDays(7),
    ]);
    $alreadyPublished = Page::factory()->create([
        'visible_from' => now()->subMonth(),
        'visible_until' => null,
    ]);
    $expired = Page::factory()->create([
        'visible_from' => now()->subYear(),
        'visible_until' => now()->subDay(),
    ]);

    $query = BuildScheduledPublishingQueryAction::run();

    expect($query)->toBeInstanceOf(Builder::class);

    $ids = $query->pluck('id')->all();

    expect($ids)->toContain($pendingPublish->id)
        ->and($ids)->toContain($pendingUnpublish->id)
        ->and($ids)->not->toContain($alreadyPublished->id)
        ->and($ids)->not->toContain($expired->id);
});
