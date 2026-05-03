<?php

declare(strict_types=1);

use Capell\Analytics\Actions\ImportLegacyPageViewsAction;
use Capell\Analytics\Enums\AnalyticsEventType;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('loads analytics migrations', function (): void {
    expect(Schema::hasTable('analytics_visits'))->toBeTrue()
        ->and(Schema::hasTable('analytics_consents'))->toBeTrue()
        ->and(Schema::hasTable('analytics_events'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_visits', 'uuid'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_visits', 'legacy_session_id'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_consents', 'categories'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_events', 'document_y'))->toBeTrue()
        ->and(Schema::hasColumn('analytics_events', 'legacy_page_view_id'))->toBeTrue();
});

it('imports legacy page views idempotently into analytics events', function (): void {
    Schema::create('page_views', function (Blueprint $table): void {
        $table->id();
        $table->string('url');
        $table->string('session_id', 64);
        $table->unsignedBigInteger('site_id')->nullable();
        $table->unsignedBigInteger('language_id')->nullable();
        $table->string('pageable_type')->nullable();
        $table->unsignedBigInteger('pageable_id')->nullable();
        $table->unsignedInteger('visits')->default(1);
        $table->unsignedBigInteger('user_id')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('viewed_at')->nullable();
    });

    DB::table('page_views')->insert([
        'id' => 1001,
        'url' => 'https://example.test/imported',
        'session_id' => str_repeat('a', 64),
        'site_id' => null,
        'language_id' => null,
        'pageable_type' => null,
        'pageable_id' => null,
        'visits' => 2,
        'user_id' => null,
        'created_at' => '2026-04-20 09:00:00',
        'viewed_at' => '2026-04-20 09:05:00',
    ]);

    expect(ImportLegacyPageViewsAction::run())->toBe(2)
        ->and(ImportLegacyPageViewsAction::run())->toBe(0)
        ->and(AnalyticsVisit::query()->where('legacy_session_id', str_repeat('a', 64))->count())->toBe(1)
        ->and(AnalyticsEvent::query()->where('legacy_page_view_id', 1001)->count())->toBe(2);

    $event = AnalyticsEvent::query()->where('legacy_page_view_id', 1001)->firstOrFail();

    expect($event->type)->toBe(AnalyticsEventType::PageView)
        ->and($event->path)->toBe('/imported');
});
