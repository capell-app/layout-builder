<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\ResolvePageContentStructureAction;
use Illuminate\Support\Facades\DB;

it('resolves a page override without loading a missing blueprint', function (?string $override, ?ContentStructure $expected): void {
    $page = new Page(['content_structure_override' => $override, 'blueprint_id' => 999999]);
    DB::enableQueryLog();

    try {
        $structure = ResolvePageContentStructureAction::run($page);
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
    }

    expect($structure)->toBe($expected)
        ->and($page->relationLoaded('blueprint'))->toBeFalse()
        ->and($queries)->toBe([]);
})->with([
    'blocks override' => ['blocks', ContentStructure::Blocks],
    'HTML override' => ['html', ContentStructure::Html],
    'absent override' => [null, null],
    'invalid override' => ['invalid', null],
]);

it('returns no structure when no page is resolved', function (): void {
    expect(ResolvePageContentStructureAction::run(null))->toBeNull();
});
