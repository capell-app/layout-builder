<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Tests\Unit\Checks;

use Capell\PublishingStudio\Checks\PublishCheckSeverity;
use Capell\PublishingStudio\Checks\SeoMetaCheck;
use Capell\PublishingStudio\Models\Workspace;
use Capell\SeoSuite\Contracts\SeoPublishReportProvider;

const SEO_PUBLISH_REPORT_PROVIDER = SeoPublishReportProvider::class;

it('uses a bound SEO publish report provider and maps critical issues to errors', function (): void {
    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [
                [
                    'page' => ['id' => 10, 'label' => 'home'],
                    'issues' => [
                        ['key' => 'meta_title', 'severity' => 'critical', 'message' => 'Missing meta title.'],
                    ],
                ],
            ];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->severity)->toBe(PublishCheckSeverity::Error)
        ->and($result->messages)->toBe(["Page 'home': Missing meta title."]);
});

it('maps warning and notice SEO issues to warnings', function (): void {
    config()->set('capell-seo-suite.publish_gates.checks', []);

    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [
                [
                    'page' => ['id' => 11, 'label' => 'about'],
                    'issues' => [
                        ['key' => 'meta_description', 'severity' => 'warning', 'message' => 'Meta description is short.'],
                        ['key' => 'search_console', 'severity' => 'notice', 'message' => 'Low impressions.'],
                    ],
                ],
            ];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->severity)->toBe(PublishCheckSeverity::Warn)
        ->and($result->messages)->toHaveCount(2);
});

it('uses configured publish gates to block and ignore SEO issues by check key', function (): void {
    config()->set('capell-seo-suite.publish_gates.checks', [
        'meta_title' => 'blocker',
        'search_console' => 'ignored',
    ]);

    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [
                [
                    'page' => ['id' => 14, 'label' => 'home'],
                    'issues' => [
                        ['key' => 'meta_title', 'severity' => 'critical', 'message' => 'Missing title.'],
                        ['key' => 'search_console', 'severity' => 'notice', 'message' => 'Low impressions.'],
                    ],
                ],
            ];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->severity)->toBe(PublishCheckSeverity::Error)
        ->and($result->messages)->toBe(["Page 'home': Missing title."]);
});

it('returns an info result when the SEO provider has no issues', function (): void {
    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->isClean())->toBeTrue()
        ->and($result->severity)->toBe(PublishCheckSeverity::Info);
});

it('can ignore a configured SEO publish check', function (): void {
    config()->set('capell-seo-suite.publish_gates.checks.search_console', 'ignored');

    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [
                [
                    'page' => ['id' => 12, 'label' => 'contact'],
                    'issues' => [
                        ['key' => 'search_console', 'severity' => 'warning', 'message' => 'Search Console setup missing.'],
                    ],
                ],
            ];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->severity)->toBe(PublishCheckSeverity::Info)
        ->and($result->messages)->toBe([]);
});

it('can downgrade critical SEO issues to warnings by check key', function (): void {
    config()->set('capell-seo-suite.publish_gates.checks.meta_title', 'warning');

    app()->instance(SEO_PUBLISH_REPORT_PROVIDER, new class
    {
        public function forWorkspace(Workspace $workspace): array
        {
            return [
                [
                    'page' => ['id' => 13, 'label' => 'services'],
                    'issues' => [
                        ['key' => 'meta_title', 'severity' => 'critical', 'message' => 'Missing meta title.'],
                    ],
                ],
            ];
        }
    });

    $result = (new SeoMetaCheck)->run(Workspace::factory()->create());

    expect($result->severity)->toBe(PublishCheckSeverity::Warn)
        ->and($result->messages)->toBe(["Page 'services': Missing meta title."]);
});
