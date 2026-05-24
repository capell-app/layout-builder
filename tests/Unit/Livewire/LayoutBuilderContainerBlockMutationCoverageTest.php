<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Block;

final class LayoutBuilderContainerBlockMutationHarness extends LayoutBuilder
{
    #[Override]
    public function assertCanUpdateLayout(): void {}

    #[Override]
    public function assertCanEditContent(): void {}

    /**
     * @param  array<string, array<int, Block>>  $containerBlocks
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
    public function exposeGetContainerBlockKeys(): array
    {
        return $this->getContainerBlockKeys();
    }

    public function exposeGetLastContainerBlockOccurrence(string $containerKey, string $blockKey, ?int $compareIndex = null): int
    {
        return $this->getLastContainerBlockOccurrence($containerKey, $blockKey, $compareIndex);
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

it('adds saves renames duplicates moves and removes containers while keeping state aligned', function (): void {
    $layout = Layout::factory()->create();
    $harness = makeLayoutBuilderMutationHarness($layout);

    $harness->addContainer('main');
    $harness->addContainer('aside');
    $harness->addContainer('hero', 0);

    expect(array_keys($harness->containers))->toBe(['hero', 'main', 'aside'])
        ->and($harness->knownContainerKeys)->toBe(['main', 'aside', 'hero'])
        ->and($harness->canMoveContainerUp('hero'))->toBeFalse()
        ->and($harness->canMoveContainerDown('hero'))->toBeTrue();

    $harness->saveContainer([
        'key' => 'primary',
        'meta' => [
            'colspan' => 8,
        ],
    ], 'main');

    expect(array_keys($harness->containers))->toBe(['hero', 'aside', 'primary'])
        ->and($harness->containers['primary']['meta']['area'])->toBe('main')
        ->and($harness->knownContainerKeys)->toContain('primary')
        ->and($harness->knownContainerKeys)->not->toContain('main');

    $harness->duplicateContainer('primary');

    $duplicatedKey = array_values(array_diff(array_keys($harness->containers), ['hero', 'aside', 'primary']))[0];

    expect($duplicatedKey)->toStartWith('container-')
        ->and($harness->selectedRecords[$duplicatedKey])->toBe([]);

    $harness->moveContainerUp($duplicatedKey);

    expect(array_keys($harness->containers)[2])->toBe($duplicatedKey);

    $harness->removeContainer('aside');

    expect($harness->containers)->not->toHaveKey('aside')
        ->and($harness->knownContainerKeys)->not->toContain('aside')
        ->and($harness->layoutModified)->toBeTrue();
});

it('mutates blocks across positions containers and occurrence metadata', function (): void {
    $layout = Layout::factory()->create();
    $page = Page::factory()->withTranslations()->create();
    $heroBlock = Block::factory()->create(['key' => 'hero']);
    $cardBlock = Block::factory()->create(['key' => 'card']);
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
            'blocks' => [],
        ],
        'aside' => [
            'blocks' => [],
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

    expect($insertedIndex)->toBe(0)
        ->and($harness->containers['main']['blocks'][0]['block_key'])->toBe('card')
        ->and($harness->containers['main']['blocks'][1]['block_key'])->toBe('hero')
        ->and($harness->canMoveBlockUp('main', 0))->toBeFalse()
        ->and($harness->canMoveBlockDown('main', 0))->toBeTrue()
        ->and($harness->canMoveBlockToContainer('main', 0, 'aside'))->toBeTrue()
        ->and($harness->canMoveBlockToAnotherContainer('main', 0))->toBeTrue();

    $harness->assets['main'][0] = $assetPayload;
    $harness->originalAssets['main'][0] = $assetPayload;
    $harness->selectedRecords['main'][0] = ['page.' . $page->getKey()];

    $harness->duplicateBlock('main', 0);

    expect($harness->containers['main']['blocks'][2]['block_key'])->toBe('card')
        ->and($harness->containers['main']['blocks'][2]['occurrence'])->toBe(3)
        ->and($harness->assets['main'][2])->toBe($assetPayload);

    $harness->editLayoutBlock('main', 2, ['html_class' => 'featured']);

    expect($harness->containers['main']['blocks'][2]['meta']['html_class'])->toBe('featured');

    $harness->moveBlockToContainer('main', 0, 'aside');

    expect($harness->containers['aside']['blocks'][0]['block_key'])->toBe('card')
        ->and($harness->containers['aside']['blocks'][0]['occurrence'])->toBe(1)
        ->and($harness->assets['aside'][0][0]['container'])->toBe('main');

    $harness->exposeNormalizeContainerBlockOccurrences('main');

    expect($harness->exposeGetContainerBlockKeys())->toBe(['hero', 'card'])
        ->and($harness->exposeGetLastContainerBlockOccurrence('main', 'card', 1))->toBe(1);

    $harness->removeBlock('main', 0);

    expect($harness->containers['main']['blocks'][0]['block_key'])->toBe('card')
        ->and($harness->assets['main'])->toHaveCount(1)
        ->and($harness->selectedRecords['main'])->toHaveCount(1);
});
