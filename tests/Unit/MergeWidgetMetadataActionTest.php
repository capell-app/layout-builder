<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\MergeWidgetMetadataAction;

it('preserves omitted metadata and replaces submitted lists at every depth', function (): void {
    $existing = [
        'unrelated' => ['retained' => true],
        'calendar' => ['source' => 'authored', 'entries' => [['title' => 'First', 'private' => true], ['title' => 'Second']]],
        'padding' => ['top', 'bottom'],
    ];
    expect(MergeWidgetMetadataAction::run($existing, [
        'calendar' => ['entries' => [['title' => 'Replacement']]], 'padding' => ['left'],
    ]))->toBe([
        'unrelated' => ['retained' => true],
        'calendar' => ['source' => 'authored', 'entries' => [['title' => 'Replacement']]],
        'padding' => ['left'],
    ]);
    expect(MergeWidgetMetadataAction::run($existing, ['calendar' => ['entries' => []], 'padding' => []]))->toBe([
        'unrelated' => ['retained' => true], 'calendar' => ['source' => 'authored', 'entries' => []], 'padding' => [],
    ]);
});

it('keeps omitted state and accepts explicit scalar and null replacements', function (): void {
    $existing = ['calendar' => ['enabled' => true, 'quietSummary' => 'Old summary'], 'custom' => 'retained'];
    expect(MergeWidgetMetadataAction::run($existing, []))->toBe($existing)
        ->and(MergeWidgetMetadataAction::run($existing, ['calendar' => ['enabled' => false, 'quietSummary' => null]]))->toBe([
            'calendar' => ['enabled' => false, 'quietSummary' => null], 'custom' => 'retained',
        ]);
});
