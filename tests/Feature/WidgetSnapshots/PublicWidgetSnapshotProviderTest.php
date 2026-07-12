<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

it('schedules installed snapshot pruning once with cluster-safe locks', function (): void {
    $events = collect(resolve(Schedule::class)->events())
        ->filter(fn ($event): bool => str_contains((string) $event->command, 'capell:widget-snapshots:prune'));

    expect($events)->toHaveCount(1);
    $event = $events->sole();
    expect($event->expression)->toBe('30 2 * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->onOneServer)->toBeTrue();
});
