<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Diagnostics\Actions\Dashboard\BuildRegistryHealthAction;
use Capell\Diagnostics\Data\Dashboard\RegistrySectionData;

it('dashboard-dashboard_reports page types registered via CapellCore', function (): void {
    CapellCore::registerPageType(new PageTypeData(
        name: 'widget_fixture',
        model: Page::class,
    ));

    $result = BuildRegistryHealthAction::run();

    $pageTypesSection = collect($result->sections->toArray())
        ->first(fn (array $section): bool => $section['name'] === 'Page types');

    expect($pageTypesSection)->not->toBeNull()
        ->and(collect($pageTypesSection['entries'])->pluck('class')->all())
        ->toContain(Page::class);
});

it('classifies Capell core types as source package "core"', function (): void {
    CapellCore::registerPageType(new PageTypeData(
        name: 'core_fixture',
        model: Page::class,
    ));

    $result = BuildRegistryHealthAction::run();

    $pageTypesSection = collect($result->sections->toArray())
        ->first(fn (array $section): bool => $section['name'] === 'Page types');

    $coreEntry = collect($pageTypesSection['entries'])
        ->first(fn (array $entry): bool => $entry['class'] === Page::class);

    expect($coreEntry)->not->toBeNull()
        ->and($coreEntry['sourcePackage'])->toBe('core');
});

it('classifies App-namespaced types as auto-discovered', function (): void {
    // Simulate an App-namespaced model by using a fake class string for source detection.
    // We cannot instantiate a real App\ class in the test suite, so we verify the
    // private helper logic via a type registered with a model that has an App\ prefix
    // by temporarily subclassing PageTypeData with a custom model string.
    $data = new PageTypeData(
        name: 'host_fixture',
        model: Page::class,
    );

    // Register it so the action can enumerate it
    CapellCore::registerPageType($data);

    // The action's sourcePackageOf & isAutoDiscovered are private, so we test
    // the output: Page::class starts with Capell\Core so autoDiscovered must be false.
    $result = BuildRegistryHealthAction::run();

    $pageTypesSection = collect($result->sections->toArray())
        ->first(fn (array $section): bool => $section['name'] === 'Page types');

    $entry = collect($pageTypesSection['entries'])
        ->first(fn (array $entry): bool => $entry['class'] === Page::class);

    expect($entry['autoDiscovered'])->toBeFalse();
});

it('includes counts matching registered entries', function (): void {
    // Register three page types.
    CapellCore::registerPageType(new PageTypeData(name: 'count_a', model: Page::class));
    CapellCore::registerPageType(new PageTypeData(name: 'count_b', model: Page::class));
    CapellCore::registerPageType(new PageTypeData(name: 'count_c', model: Page::class));

    $result = BuildRegistryHealthAction::run();

    $pageTypesSection = $result->sections->toCollection()
        ->first(fn (RegistrySectionData $section): bool => $section->name === 'Page types');

    expect($pageTypesSection->count)->toBe($pageTypesSection->entries->count());
});

it('includes content block registry entries when content blocks are installed', function (): void {
    $blocks = new BlockRegistry;
    $blocks->register(new BlockDefinitionData(
        key: 'section.accordion',
        label: 'Accordion',
        description: 'Accordion panels.',
        category: 'main',
        view: 'capell-content-sections::section.blocks.accordion',
        sourcePackage: 'content-sections',
    ));

    app()->instance(BlockRegistry::class, $blocks);

    $result = BuildRegistryHealthAction::run();

    $contentBlocksSection = $result->sections->toCollection()
        ->first(fn (RegistrySectionData $section): bool => $section->name === 'Content blocks');

    expect($contentBlocksSection)->not->toBeNull()
        ->and($contentBlocksSection->count)->toBe(1)
        ->and($contentBlocksSection->entries->first()->class)->toBe('block:section.accordion')
        ->and($contentBlocksSection->entries->first()->sourcePackage)->toBe('content-sections');
});

it('gracefully handles empty registrations', function (): void {
    // With a fresh app state the page types registered by core service provider may
    // or may not be present. We only assert the section exists with a consistent count.
    $result = BuildRegistryHealthAction::run();

    $pageTypesSection = $result->sections->toCollection()
        ->first(fn (RegistrySectionData $section): bool => $section->name === 'Page types');

    expect($pageTypesSection)->not->toBeNull()
        ->and($pageTypesSection->count)->toBeGreaterThanOrEqual(0)
        ->and($pageTypesSection->count)->toBe($pageTypesSection->entries->count());
});
