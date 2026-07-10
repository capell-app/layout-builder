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
        'nested' => dependencyWidgetExtensionBlock('dependency-instance', ['title' => 'Dependencies']),
    ]]);

    expect($dependencies)->toHaveCount(2)
        ->and($dependencies[0]->modelType)->toBe(Media::class)
        ->and($dependencies[0]->modelId)->toBe(12)
        ->and($dependencies[1]->modelType)->toBe(Page::class)
        ->and($dependencies[1]->modelId)->toBe(44);
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
