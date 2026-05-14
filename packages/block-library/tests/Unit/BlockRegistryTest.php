<?php

declare(strict_types=1);

use Capell\ContentBlocks\Actions\ListBlockDefinitionsAction;
use Capell\ContentBlocks\Actions\RegisterBlockDefinitionProviderAction;
use Capell\ContentBlocks\Actions\ResolveBlockDefinitionAction;
use Capell\ContentBlocks\Contracts\BlockDefinitionProvider;
use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistry;

it('registers typed content block definitions', function (): void {
    $registry = new BlockRegistry;
    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
        defaults: ['alignment' => 'center'],
    );

    $registry->register($definition);

    expect($registry->has('marketing.hero'))->toBeTrue()
        ->and($registry->getOrFail('marketing.hero'))->toBe($definition)
        ->and($registry->forCategory('marketing'))->toBe(['marketing.hero' => $definition]);
});

it('guards against duplicate block keys', function (): void {
    $registry = new BlockRegistry;
    $definition = new BlockDefinitionData(
        key: 'marketing.hero',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
    );

    $registry->register($definition);
    $registry->register($definition);
})->throws(InvalidArgumentException::class, 'Content block [marketing.hero] is already registered.');

it('rejects incomplete block definitions', function (): void {
    new BlockDefinitionData(
        key: '',
        label: 'Marketing hero',
        description: 'A campaign-ready hero block.',
        category: 'marketing',
        view: 'vendor-package::blocks.marketing-hero',
    );
})->throws(InvalidArgumentException::class, 'Block definition [key] must not be empty.');

it('registers definitions from providers through the action boundary', function (): void {
    $registry = new BlockRegistry;
    $provider = new class implements BlockDefinitionProvider
    {
        public function definitions(): iterable
        {
            yield new BlockDefinitionData(
                key: 'editorial.quote',
                label: 'Editorial quote',
                description: 'A pull quote block.',
                category: 'editorial',
                view: 'vendor-package::blocks.quote',
            );
        }
    };

    RegisterBlockDefinitionProviderAction::run($registry, $provider);

    expect($registry->get('editorial.quote')?->view)->toBe('vendor-package::blocks.quote');
});

it('lists and resolves definitions from the container registry', function (): void {
    $registry = resolve(BlockRegistry::class);
    $registry->register(new BlockDefinitionData(
        key: 'shared.media',
        label: 'Shared media',
        description: 'A reusable media block.',
        category: 'media',
        view: 'vendor-package::blocks.media',
    ));

    expect(ListBlockDefinitionsAction::run())->toHaveKey('shared.media')
        ->and(ResolveBlockDefinitionAction::run('shared.media')->label)->toBe('Shared media');
});

it('registers tagged providers when the registry is resolved', function (): void {
    app()->bind('test.content-block-provider', static fn (): BlockDefinitionProvider => new class implements BlockDefinitionProvider
    {
        public function definitions(): iterable
        {
            yield new BlockDefinitionData(
                key: 'commerce.price-card',
                label: 'Price card',
                description: 'A pricing card block.',
                category: 'commerce',
                view: 'vendor-package::blocks.price-card',
            );
        }
    });

    app()->tag(['test.content-block-provider'], BlockDefinitionProvider::TAG);

    expect(resolve(BlockRegistry::class)->get('commerce.price-card')?->label)->toBe('Price card');
});
