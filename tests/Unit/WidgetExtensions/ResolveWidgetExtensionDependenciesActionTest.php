<?php

declare(strict_types=1);

use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\WidgetExtensions\ResolveWidgetExtensionDependenciesAction;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingDependencyResolver;

it('extracts deduplicated validated dependencies from nested typed widget input', function (): void {
    RecordingDependencyResolver::$identifiers = [
        'media:12',
        'media:12',
        'content:page:44',
        'media:0',
        'content:unknown:3',
        'javascript:alert(1)',
    ];
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        dependencyResolver: RecordingDependencyResolver::class,
    ));

    $dependencies = resolve(ResolveWidgetExtensionDependenciesAction::class)->resolve([[
        'target_widget' => dependencyWidgetExtensionBlock('dependency-instance', ['title' => 'Dependencies']),
    ]]);

    expect($dependencies)->toHaveCount(2)
        ->and($dependencies[0]->modelType)->toBe(Media::class)
        ->and($dependencies[0]->modelId)->toBe(12)
        ->and($dependencies[1]->modelType)->toBe(Page::class)
        ->and($dependencies[1]->modelId)->toBe(44);
});

it('bounds resolver output and rejects integer overflow and non-string identifiers', function (): void {
    RecordingDependencyResolver::$identifiers = [
        'media:' . str_repeat('9', 500),
        'media:' . ((string) PHP_INT_MAX) . '0',
        123,
        'media:1',
        ...array_map(static fn (int $identifier): string => 'media:' . ($identifier + 2), range(0, 1000)),
    ];
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        dependencyResolver: RecordingDependencyResolver::class,
    ));

    $dependencies = resolve(ResolveWidgetExtensionDependenciesAction::class)->resolve([
        dependencyWidgetExtensionBlock('bounded-dependencies', ['title' => 'Bounds']),
    ]);

    expect($dependencies)->toHaveCount(61)
        ->and(collect($dependencies)->pluck('modelId')->all())->toContain(1)
        ->not->toContain(0, PHP_INT_MAX);
});

it('does not resolve dependencies hidden inside an unavailable widget payload', function (): void {
    RecordingDependencyResolver::$identifiers = ['media:91'];
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        dependencyResolver: RecordingDependencyResolver::class,
    ));

    $dependencies = resolve(ResolveWidgetExtensionDependenciesAction::class)->resolve([[
        'type' => 'unknown.widget',
        'data' => ['nested' => dependencyWidgetExtensionBlock('hidden', ['title' => 'Hidden'])],
    ]]);

    expect($dependencies)->toBe([]);
});

/** @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function dependencyWidgetExtensionBlock(string $identity, array $data): array
{
    return [
        'type' => 'capell-app.slideshow',
        'data' => [...$data, '__capell' => ['instance_id' => $identity, 'state_version' => 2]],
    ];
}
