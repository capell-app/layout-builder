<?php

declare(strict_types=1);

use Capell\Workspaces\ReleaseWindowGuard;
use Carbon\CarbonImmutable;

it('reports open when the feature is disabled', function (): void {
    config()->set('capell.workspaces.release_windows.enabled', false);

    expect((new ReleaseWindowGuard)->isOpen())->toBeTrue();
});

it('reports open inside a configured day-and-time window', function (): void {
    config()->set('capell.workspaces.release_windows.enabled', true);
    config()->set('capell.workspaces.release_windows.timezone', 'UTC');
    config()->set('capell.workspaces.release_windows.windows', [
        ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    $tuesdayMorning = CarbonImmutable::parse('2026-04-14 10:00:00', 'UTC');
    $tuesdayEvening = CarbonImmutable::parse('2026-04-14 20:00:00', 'UTC');
    $saturday = CarbonImmutable::parse('2026-04-18 10:00:00', 'UTC');

    $guard = new ReleaseWindowGuard;

    expect($guard->isOpen($tuesdayMorning))->toBeTrue()
        ->and($guard->isOpen($tuesdayEvening))->toBeFalse()
        ->and($guard->isOpen($saturday))->toBeFalse();
});

it('handles ranges that cross midnight', function (): void {
    config()->set('capell.workspaces.release_windows.enabled', true);
    config()->set('capell.workspaces.release_windows.timezone', 'UTC');
    config()->set('capell.workspaces.release_windows.windows', [
        ['days' => ['fri'], 'start' => '22:00', 'end' => '02:00'],
    ]);

    $guard = new ReleaseWindowGuard;

    expect($guard->isOpen(CarbonImmutable::parse('2026-04-17 23:00:00', 'UTC')))->toBeTrue()
        ->and($guard->isOpen(CarbonImmutable::parse('2026-04-18 01:30:00', 'UTC')))->toBeTrue()
        ->and($guard->isOpen(CarbonImmutable::parse('2026-04-18 03:00:00', 'UTC')))->toBeFalse()
        ->and($guard->isOpen(CarbonImmutable::parse('2026-04-17 21:00:00', 'UTC')))->toBeFalse();
});

it('respects the configured timezone', function (): void {
    config()->set('capell.workspaces.release_windows.enabled', true);
    config()->set('capell.workspaces.release_windows.timezone', 'Europe/London');
    config()->set('capell.workspaces.release_windows.windows', [
        ['days' => ['mon'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    $guard = new ReleaseWindowGuard;

    // 09:00 BST (summer) in Europe/London = 08:00 UTC.
    $utcMoment = CarbonImmutable::parse('2026-06-15 08:30:00', 'UTC');

    expect($guard->isOpen($utcMoment))->toBeTrue();
});

it('returns the next opening moment when currently closed', function (): void {
    config()->set('capell.workspaces.release_windows.enabled', true);
    config()->set('capell.workspaces.release_windows.timezone', 'UTC');
    config()->set('capell.workspaces.release_windows.windows', [
        ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'start' => '09:00', 'end' => '17:00'],
    ]);

    $saturday = CarbonImmutable::parse('2026-04-18 10:00:00', 'UTC');

    $nextOpensAt = (new ReleaseWindowGuard)->nextOpensAt($saturday);

    expect($nextOpensAt)->not->toBeNull()
        ->and($nextOpensAt->format('Y-m-d H:i'))->toBe('2026-04-20 09:00');
});

it('returns null from nextOpensAt when the feature is disabled', function (): void {
    config()->set('capell.workspaces.release_windows.enabled', false);

    expect((new ReleaseWindowGuard)->nextOpensAt())->toBeNull();
});
