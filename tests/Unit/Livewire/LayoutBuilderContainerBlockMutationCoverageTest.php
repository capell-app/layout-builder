<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;

final class LayoutBuilderContainerBlockMutationHarness extends LayoutBuilder
{
    #[Override]
    public function assertCanUpdateLayout(): void {}

    #[Override]
    public function assertCanEditContent(): void {}

    /**
     * @param  array<string, array<int, Widget>>  $containerBlocks
     */
    public function setContainerBlocks(array $containerBlocks): void
    {
        $this->containerBlocks = $containerBlocks;
    }

    public function exposeNormalizeContainerBlockOccurrences(string $containerKey): void
    {
        $this->normalizeContainerBlockOccurrences($containerKey);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeGetContainerWidgetKeys(): array
    {
        return $this->getContainerWidgetKeys();
    }

    public function exposeGetLastContainerBlockOccurrence(string $containerKey, string $widgetKey, ?int $compareIndex = null): int
    {
        return $this->getLastContainerBlockOccurrence($containerKey, $widgetKey, $compareIndex);
    }

    #[Override]
    protected function assertCanEditLayout(): void {}
}

function makeLayoutBuilderMutationHarness(Layout $layout): LayoutBuilderContainerBlockMutationHarness
{
    $harness = new LayoutBuilderContainerBlockMutationHarness;
    $harness->layout = $layout;
    $harness->containers = [];
    $harness->assets = [];
    $harness->selectedRecords = [];
    $harness->originalAssets = [];
    $harness->knownContainerKeys = [];
    $harness->setContainerBlocks([]);

    return $harness;
}

/**
 * @return array<string, array<string, mixed>>
 */
function layoutBuilderMutationContainers(LayoutBuilderContainerBlockMutationHarness $harness): array
{
    /** @var array<string, array<string, mixed>> $containers */
    $containers = capell_test_array($harness->containers);

    return $containers;
}

/**
 * @param  array<string, mixed>  $container
 * @return array<int, array<string, mixed>>
 */
function layoutBuilderMutationWidgets(array $container): array
{
    throw_unless(is_array($container['widgets'] ?? null), RuntimeException::class, 'Expected container widgets array.');

    /** @var array<int, array<string, mixed>> $widgets */
    $widgets = $container['widgets'];

    return $widgets;
}

it('adds saves renames duplicates moves and removes containers while keeping state aligned', function (): void {
    $layout = Layout::factory()->create();
    $harness = makeLayoutBuilderMutationHarness($layout);

    $harness->addContainer('main');
    $harness->addContainer('aside');
    $harness->addContainer('hero', 0);

    $containers = layoutBuilderMutationContainers($harness);

    capell_expect(array_keys($containers))->toBe(['hero', 'main', 'aside'])
        ->and($harness->knownContainerKeys)->toBe(['main', 'aside', 'hero'])
        ->and($harness->canMoveContainerUp('hero'))->toBeFalse()
        ->and($harness->canMoveContainerDown('hero'))->toBeTrue();

    $harness->saveContainer([
        'key' => 'primary',
        'meta' => [
            'colspan' => 8,
        ],
    ], 'main');

    $containers = layoutBuilderMutationContainers($harness);
    $primaryContainer = capell_test_array($containers['primary'] ?? null);
    $primaryContainerMeta = capell_test_array($primaryContainer['meta'] ?? null);

    capell_expect(array_keys($containers))->toBe(['hero', 'aside', 'primary'])
        ->and($primaryContainerMeta['area'] ?? null)->toBe('main')
        ->and($harness->knownContainerKeys)->toContain('primary')
        ->and($harness->knownContainerKeys)->not->toContain('main');

    $harness->duplicateContainer('primary');

    $duplicatedKey = null;
    $containers = layoutBuilderMutationContainers($harness);
    foreach (array_keys($containers) as $containerKey) {
        if (! in_array($containerKey, ['hero', 'aside', 'primary'], true)) {
            $duplicatedKey = $containerKey;
            break;
        }
    }

    throw_unless(is_string($duplicatedKey));

    capell_expect($duplicatedKey)->toStartWith('container-')
        ->and($harness->selectedRecords[$duplicatedKey])->toBe([]);

    $harness->moveContainerUp($duplicatedKey);

    $containers = layoutBuilderMutationContainers($harness);

    capell_expect(array_keys($containers)[2] ?? null)->toBe($duplicatedKey);

    $harness->removeContainer('aside');

    $containers = layoutBuilderMutationContainers($harness);

    capell_expect($containers)->not->toHaveKey('aside')
        ->and($harness->knownContainerKeys)->not->toContain('aside')
        ->and($harness->layoutModified)->toBeTrue();
});

it('mutates blocks across positions containers and occurrence metadata', function (): void {
    $layout = Layout::factory()->create();
    $page = Page::factory()->withTranslations()->create();
    $heroBlock = Widget::factory()->create(['key' => 'hero']);
    $cardBlock = Widget::factory()->create(['key' => 'card']);
    $assetPayload = [
        [
            'asset_type' => $page->getMorphClass(),
            'asset_id' => $page->getKey(),
            'order' => 1,
            'occurrence' => 1,
            'container' => 'main',
        ],
    ];

    $harness = makeLayoutBuilderMutationHarness($layout);
    $harness->containers = [
        'main' => [
            'widgets' => [],
        ],
        'aside' => [
            'widgets' => [],
        ],
    ];
    $harness->assets = [
        'main' => [],
        'aside' => [],
    ];
    $harness->selectedRecords = [
        'main' => [],
        'aside' => [],
    ];
    $harness->knownContainerKeys = ['main', 'aside'];
    $harness->setContainerBlocks([
        'main' => [],
        'aside' => [],
    ]);

    $harness->addBlockToContainer($heroBlock, 'main');

    $insertedIndex = $harness->addBlockToContainerAtPosition($cardBlock, 'main', 0);

    $containers = layoutBuilderMutationContainers($harness);
    $mainContainer = capell_test_array($containers['main'] ?? null);
    $mainWidgets = layoutBuilderMutationWidgets($mainContainer);

    capell_expect($insertedIndex)->toBe(0)
        ->and($mainWidgets[0]['widget_key'] ?? null)->toBe('card')
        ->and($mainWidgets[1]['widget_key'] ?? null)->toBe('hero')
        ->and($harness->canMoveBlockUp('main', 0))->toBeFalse()
        ->and($harness->canMoveBlockDown('main', 0))->toBeTrue()
        ->and($harness->canMoveBlockToContainer('main', 0, 'aside'))->toBeTrue()
        ->and($harness->canMoveBlockToAnotherContainer('main', 0))->toBeTrue();

    $harness->assets['main'][0] = $assetPayload;
    $harness->originalAssets['main'][0] = $assetPayload;
    $harness->selectedRecords['main'][0] = ['page.' . $page->getKey()];

    $harness->duplicateBlock('main', 0);

    $containers = layoutBuilderMutationContainers($harness);
    $mainContainer = capell_test_array($containers['main'] ?? null);
    $mainWidgets = layoutBuilderMutationWidgets($mainContainer);

    capell_expect($mainWidgets[2]['widget_key'] ?? null)->toBe('card')
        ->and($mainWidgets[2]['occurrence'] ?? null)->toBe(3)
        ->and($harness->assets['main'][2])->toBe($assetPayload);

    $harness->editLayoutBlock('main', 2, ['html_class' => 'featured']);

    $containers = layoutBuilderMutationContainers($harness);
    $mainContainer = capell_test_array($containers['main'] ?? null);
    $mainWidgets = layoutBuilderMutationWidgets($mainContainer);
    $thirdWidgetMeta = capell_test_array($mainWidgets[2]['meta'] ?? null);

    capell_expect($thirdWidgetMeta['html_class'] ?? null)->toBe('featured');

    $harness->moveBlockToContainer('main', 0, 'aside');

    $containers = layoutBuilderMutationContainers($harness);
    $asideContainer = capell_test_array($containers['aside'] ?? null);
    $asideWidgets = layoutBuilderMutationWidgets($asideContainer);

    capell_expect($asideWidgets[0]['widget_key'] ?? null)->toBe('card')
        ->and($asideWidgets[0]['occurrence'] ?? null)->toBe(1)
        ->and($harness->assets['aside'][0][0]['container'])->toBe('main');

    $harness->exposeNormalizeContainerBlockOccurrences('main');

    capell_expect($harness->exposeGetContainerWidgetKeys())->toBe(['hero', 'card'])
        ->and($harness->exposeGetLastContainerBlockOccurrence('main', 'card', 1))->toBe(1);

    $harness->removeBlock('main', 0);

    $containers = layoutBuilderMutationContainers($harness);
    $mainContainer = capell_test_array($containers['main'] ?? null);
    $mainWidgets = layoutBuilderMutationWidgets($mainContainer);

    capell_expect($mainWidgets[0]['widget_key'] ?? null)->toBe('card')
        ->and($harness->assets['main'])->toHaveCount(1)
        ->and($harness->selectedRecords['main'])->toHaveCount(1);
});
