<?php

declare(strict_types=1);

use Capell\Insights\Actions\ImportLegacyPageViewsAction;
use Capell\Insights\Enums\InsightsEventType;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('loads insights migrations', function (): void {
    expect(Schema::hasTable('insights_visits'))->toBeTrue()
        ->and(Schema::hasTable('insights_consents'))->toBeTrue()
        ->and(Schema::hasTable('insights_events'))->toBeTrue()
        ->and(Schema::hasColumn('insights_visits', 'uuid'))->toBeTrue()
        ->and(Schema::hasColumn('insights_visits', 'legacy_session_id'))->toBeTrue()
        ->and(Schema::hasColumn('insights_consents', 'categories'))->toBeTrue()
        ->and(Schema::hasColumn('insights_events', 'document_y'))->toBeTrue()
        ->and(Schema::hasColumn('insights_events', 'legacy_page_view_id'))->toBeTrue()
        ->and(Schema::hasColumn('page_urls', 'hit_count'))->toBeTrue()
        ->and(Schema::hasColumn('page_urls', 'last_hit_at'))->toBeTrue()
        ->and(Schema::hasIndex('insights_events', 'insights_events_path_type_occurred_index'))->toBeTrue();
});

it('imports legacy page views idempotently into insights events', function (): void {
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
        ->and(InsightsVisit::query()->where('legacy_session_id', str_repeat('a', 64))->count())->toBe(1)
        ->and(InsightsEvent::query()->where('legacy_page_view_id', 1001)->count())->toBe(2);

    $event = InsightsEvent::query()->where('legacy_page_view_id', 1001)->firstOrFail();

    expect($event->type)->toBe(InsightsEventType::PageView)
        ->and($event->path)->toBe('/imported');
});
