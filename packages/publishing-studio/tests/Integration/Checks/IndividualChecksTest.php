<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Tests\Integration\Checks;

use Capell\PublishingStudio\Checks\AccessibilityCheck;
use Capell\PublishingStudio\Checks\BrokenLinkCheck;
use Capell\PublishingStudio\Checks\MissingAltTextCheck;
use Capell\PublishingStudio\Checks\PublishCheckSeverity;
use Capell\PublishingStudio\Checks\SeoMetaCheck;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// ---------------------------------------------------------------------------
// Schema helpers — add columns needed by checks if they are not yet present
// ---------------------------------------------------------------------------

function ensurePagesSEOColumns(): void
{
    Schema::table('pages', function (Blueprint $table): void {
        if (! Schema::hasColumn('pages', 'meta_title')) {
            $table->string('meta_title')->nullable();
        }

        if (! Schema::hasColumn('pages', 'meta_description')) {
            $table->text('meta_description')->nullable();
        }
    });
}

function ensurePagesBodyColumn(): void
{
    Schema::table('pages', function (Blueprint $table): void {
        if (! Schema::hasColumn('pages', 'body')) {
            $table->longText('body')->nullable();
        }
    });
}

function ensurePageUrlsTable(): void
{
    if (! Schema::hasTable('page_urls')) {
        Schema::create('page_urls', function (Blueprint $table): void {
            $table->id();
            $table->string('url');
            $table->unsignedBigInteger('workspace_id')->default(0)->index();
            $table->timestamps();
        });
    }
}

function insertBasePage(Workspace $workspace, array $overrides = []): string
{
    $uuid = (string) Str::uuid();

    DB::table('pages')->insert(array_merge([
        'uuid' => $uuid,
        'name' => 'Test Page',
        'workspace_id' => $workspace->id,
        'type_id' => 1,
        'layout_id' => 1,
        'site_id' => 1,
    ], $overrides));

    return $uuid;
}

// ---------------------------------------------------------------------------
// SeoMetaCheck
// ---------------------------------------------------------------------------

describe('SeoMetaCheck', function (): void {
    beforeEach(function (): void {
        ensurePagesSEOColumns();
    });

    it('dashboard-dashboard_reports pages that are missing meta title or meta description', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'meta_title' => '',
            'meta_description' => null,
        ]);

        $check = new SeoMetaCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeFalse()
            ->and($result->severity)->toBe(PublishCheckSeverity::Warn)
            ->and($result->messages)->toHaveCount(1);
    });

    it('returns a clean result when all pages have meta title and description', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'meta_title' => 'My Page Title',
            'meta_description' => 'A useful description of the page.',
        ]);

        $check = new SeoMetaCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeTrue()
            ->and($result->severity)->toBe(PublishCheckSeverity::Info);
    });
});

// ---------------------------------------------------------------------------
// MissingAltTextCheck
// ---------------------------------------------------------------------------

describe('MissingAltTextCheck', function (): void {
    beforeEach(function (): void {
        ensurePagesBodyColumn();
    });

    it('dashboard-dashboard_reports pages that have images missing alt text', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<p>Some content</p><img src="/photo.jpg"><img src="/ok.jpg" alt="A nice photo">',
        ]);

        $check = new MissingAltTextCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeFalse()
            ->and($result->severity)->toBe(PublishCheckSeverity::Warn)
            ->and($result->messages)->toHaveCount(1);
    });

    it('returns a clean result when all images have alt text', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<p>Content</p><img src="/photo.jpg" alt="Descriptive alt text">',
        ]);

        $check = new MissingAltTextCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeTrue()
            ->and($result->severity)->toBe(PublishCheckSeverity::Info);
    });
});

// ---------------------------------------------------------------------------
// BrokenLinkCheck
// ---------------------------------------------------------------------------

describe('BrokenLinkCheck', function (): void {
    beforeEach(function (): void {
        ensurePagesBodyColumn();
        ensurePageUrlsTable();
    });

    it('dashboard-dashboard_reports internal links that do not exist in page_urls', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<a href="/about-us">About</a><a href="/contact">Contact</a>',
        ]);

        // Only register one of the two URLs.
        // site_id and language_id are NOT NULL in the real schema; supply
        // placeholder values (SQLite does not enforce FK integrity by default).
        DB::table('page_urls')->insert([
            'url' => '/about-us',
            'workspace_id' => 0,
            'site_id' => 1,
            'language_id' => 1,
        ]);

        $check = new BrokenLinkCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeFalse()
            ->and($result->severity)->toBe(PublishCheckSeverity::Error)
            ->and($result->messages)->toHaveCount(1);
    });

    it('returns a clean result when all internal links resolve', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<a href="/about-us">About</a>',
        ]);

        // site_id and language_id are NOT NULL in the real schema; supply
        // placeholder values (SQLite does not enforce FK integrity by default).
        DB::table('page_urls')->insert([
            'url' => '/about-us',
            'workspace_id' => 0,
            'site_id' => 1,
            'language_id' => 1,
        ]);

        $check = new BrokenLinkCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeTrue()
            ->and($result->severity)->toBe(PublishCheckSeverity::Info);
    });
});

// ---------------------------------------------------------------------------
// AccessibilityCheck
// ---------------------------------------------------------------------------

describe('AccessibilityCheck', function (): void {
    beforeEach(function (): void {
        ensurePagesBodyColumn();
    });

    it('dashboard-dashboard_reports empty anchor tags', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<a href="/page"></a><p>Normal content</p>',
        ]);

        $check = new AccessibilityCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeFalse()
            ->and($result->severity)->toBe(PublishCheckSeverity::Warn)
            ->and($result->messages)->toHaveCount(1);
    });

    it('dashboard-dashboard_reports images used as links without alt text', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<a href="/page"><img src="/logo.png"></a>',
        ]);

        $check = new AccessibilityCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeFalse()
            ->and($result->severity)->toBe(PublishCheckSeverity::Warn);
    });

    it('returns a clean result for accessible HTML', function (): void {
        $workspace = Workspace::factory()->create();

        insertBasePage($workspace, [
            'body' => '<a href="/page">Go to page</a><a href="/img"><img src="/logo.png" alt="Our logo"></a>',
        ]);

        $check = new AccessibilityCheck;
        $result = $check->run($workspace);

        expect($result->isClean())->toBeTrue()
            ->and($result->severity)->toBe(PublishCheckSeverity::Info);
    });
});
