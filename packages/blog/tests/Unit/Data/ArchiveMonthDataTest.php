<?php

declare(strict_types=1);

use Capell\Blog\Data\ArchiveMonthData;

it('creates archive month data with year month and total', function (): void {
    $data = new ArchiveMonthData(2025, 3, 5);

    expect($data->year)->toBe(2025)
        ->and($data->month)->toBe(3)
        ->and($data->total)->toBe(5);
});

it('has default total of 0', function (): void {
    $data = new ArchiveMonthData(2025, 6);

    expect($data->year)->toBe(2025)
        ->and($data->month)->toBe(6)
        ->and($data->total)->toBe(0);
});
