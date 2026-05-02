<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('loads analytics migrations', function (): void {
    expect(Schema::hasTable('analytics_visits'))->toBeTrue()
        ->and(Schema::hasTable('analytics_consents'))->toBeTrue()
        ->and(Schema::hasTable('analytics_events'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_visits', 'uuid'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_consents', 'categories'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_events', 'document_y'))->toBeTrue();
});
