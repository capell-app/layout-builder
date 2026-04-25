<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\get;

uses(TestingFrontend::class);

/**
 * Query log for test assertions.
 *
 * @var array<int, array<string, mixed>>
 */
static $testQueryLog = [];

function setupQueryLogging(): void
{
    global $testQueryLog;
    $testQueryLog = [];

    DB::listen(function (QueryExecuted $query) use (&$testQueryLog): void {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 100))
            ->map(fn (array $frame): ?string => isset($frame['file']) ? $frame['file'] . ':' . ($frame['line'] ?? '?') : null)
            ->filter();
        $testQueryLog[] = [
            'query' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'trace' => $trace,
        ];
    });
}

function getTestQueryLog(): array
{
    global $testQueryLog;

    return $testQueryLog;
}

function clearTestQueryLog(): void
{
    global $testQueryLog;
    $testQueryLog = [];
}

/**
 * @param  array{query:string, bindings:array<int, mixed>, time:float, trace:Collection<int, string>}  $queryEntry
 */
function buildQuerySignature(array $queryEntry): string
{
    return $queryEntry['query'] . '|' . serialize($queryEntry['bindings']);
}

it('loads a large, deeply nested page with navigation efficiently', function (): void {
    $languageCount = 3;
    $siteCount = 2;
    $mediaPerSite = 5;
    $ancestorDepth = 5;
    $childrenPerAncestor = 10;

    $languages = Language::factory()->count($languageCount)->create();

    $ancestor = null;

    $site = Site::factory()
        ->hasTranslations(['language_id' => $languages->first()->id])
        ->enabled()
        ->state([
            'language_id' => $languages->first()->id,
        ])
        ->create();

    SiteDomain::factory()
        ->enabled()
        ->state([
            'site_id' => $site->id,
        ])
        ->forEachSequence(
            ...collect($languages)->map(fn (Language $language): array => [
                'language_id' => $language->id,
                'path' => '/' . Str::lower($language->code),
            ])->all(),
        )
        ->create();

    foreach (range(1, $mediaPerSite) as $mediaIndex) {
        Media::factory()
            ->state([
                'model_id' => $site->id,
                'model_type' => Site::class,
                'file_name' => 'media_' . $mediaIndex . '.jpg',
            ])
            ->create();
    }

    foreach (range(1, $ancestorDepth) as $depth) {
        $page = Page::factory()
            ->recycle($site)
            ->withTranslations($languages)
            ->state([
                'parent_id' => $ancestor?->id,
            ])
            ->create();

        $ancestor = $page;

        foreach (range(1, $childrenPerAncestor) as $childIndex) {
            $childPage = Page::factory()
                ->recycle($site)
                ->withTranslations($languages)
                ->state([
                    'parent_id' => $ancestor->id,
                ])
                ->create();
        }
    }

    $navigations = NavigationHandle::cases();
    Navigation::factory()
        ->for($site)
        ->items($site->pages()->with('translation')->get()->toTree())
        ->forEachSequence(
            ...collect($navigations)->map(fn (NavigationHandle $navigation): array => [
                'key' => $navigation->value,
            ]),
        )
        ->create();

    $url = $ancestor->pageUrl->full_url;

    setupQueryLogging();
    clearTestQueryLog();

    $start = microtime(true);

    $response = get($url);

    $durationMs = (microtime(true) - $start) * 1000;

    $queryLog = collect(getTestQueryLog());
    $queryCount = $queryLog->count();

    $response->assertOk();

    $duplicateQueries = $queryLog
        ->groupBy(fn (array $query): string => buildQuerySignature($query))
        ->filter(fn (Collection $queries): bool => $queries->count() > 1);

    $firstDuplicate = $duplicateQueries->first();

    if ($firstDuplicate !== null) {
        $firstQuery = $firstDuplicate->first();
        $secondQuery = $firstDuplicate->last();
        throw new RuntimeException(
            "Duplicate query detected:\n{$firstQuery['query']}\nBindings: "
            . print_r($firstQuery['bindings'], true)
            . "\nTime: {$firstQuery['time']}ms"
            . "\nTrace: " . $firstQuery['trace']->join("\n")
            . "\n\nSecond Trace " . $secondQuery['trace']->join("\n"),
        );
    }

    expect($queryCount)
        ->toBeLessThanOrEqual(33, 'Query count exceeded: ' . $queryCount)
        ->and($durationMs)
        ->toBeLessThan(2000, sprintf('Page load time exceeded: %sms', $durationMs));
});
