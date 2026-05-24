<?php

declare(strict_types=1);

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\AddBlockToLayoutContainerAction;
use Capell\LayoutBuilder\Actions\ApplyLayoutSidebarBlockContributionsAction;
use Capell\LayoutBuilder\Actions\FindReusableBlocksAction;
use Capell\LayoutBuilder\Actions\GetBlockContainerWidthAction;
use Capell\LayoutBuilder\Actions\HeroBlockHasPrimaryHeadingAction;
use Capell\LayoutBuilder\Actions\MakeBlockAction;
use Capell\LayoutBuilder\Contracts\LayoutSidebarBlockContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarBlockData;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class LayoutBuilderResidualSidebarContributor implements LayoutSidebarBlockContributor
{
    public function sidebarBlocks(): array
    {
        return [
            new LayoutSidebarBlockData('sidebar-search', ['content'], ['compact' => true]),
            new LayoutSidebarBlockData('missing-sidebar-block'),
            new LayoutSidebarBlockData('other-layout-only', ['landing']),
        ];
    }
}

final class LayoutBuilderResidualFrontendContext
{
    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function setFrontendData(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}

it('builds block seeder snippets', function (): void {
    $snippet = resolve(MakeBlockAction::class)->seederSnippet('promo-card', 'Promo Card');

    expect($snippet)->toContain("'key' => 'promo-card'")
        ->and($snippet)->toContain("'name' => 'Promo Card'");
});

it('rejects empty block scaffold names', function (): void {
    MakeBlockAction::run('');
})->throws(RuntimeException::class, 'Widget name is required.');

it('adds layout blocks with occurrences and skips existing blocks when requested', function (): void {
    $block = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'hero', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    AddBlockToLayoutContainerAction::run($block, $layout, 'main');

    expect($layout->refresh()->containers['main']['widgets'])->toHaveCount(2)
        ->and($layout->containers['main']['widgets'][1])->toBe([
            'widget_key' => 'hero',
            'occurrence' => 2,
        ]);

    AddBlockToLayoutContainerAction::run($block, $layout, 'main', skipExists: true);

    expect($layout->refresh()->containers['main']['widgets'])->toHaveCount(2);
});

it('throws when adding a block to a missing layout container', function (): void {
    $block = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => []]);

    AddBlockToLayoutContainerAction::run($block, $layout, 'missing');
})->throws(RuntimeException::class, "Container 'missing' not found in layout.");

it('applies sidebar contributions only for existing applicable blocks', function (): void {
    app()->bind(
        LayoutBuilderResidualSidebarContributor::class,
        fn (): LayoutBuilderResidualSidebarContributor => new LayoutBuilderResidualSidebarContributor,
    );
    app()->tag([LayoutBuilderResidualSidebarContributor::class], LayoutSidebarBlockContributor::TAG);

    Widget::factory()->create(['key' => 'sidebar-search']);
    Widget::factory()->create(['key' => 'other-layout-only']);

    $layout = Layout::factory()->create([
        'key' => 'content',
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'body'],
                ],
            ],
        ],
    ]);

    ApplyLayoutSidebarBlockContributionsAction::run($layout);
    ApplyLayoutSidebarBlockContributionsAction::run($layout->refresh());

    expect($layout->refresh()->containers['sidebar']['meta']['colspan'])->toBe(3)
        ->and($layout->containers['sidebar']['widgets'])->toBe([
            [
                'widget_key' => 'sidebar-search',
                'meta' => ['compact' => true],
            ],
        ])
        ->and($layout->widgets)->toBe(['body', 'sidebar-search']);
});

it('resolves block container widths from meta defaults and frontend resolver services', function (): void {
    $block = Widget::factory()->create(['meta' => ['container' => ContainerWidthEnum::Small->value]]);

    expect(GetBlockContainerWidthAction::run($block))->toBe(ContainerWidthEnum::Small);

    $block->forceFill(['meta' => []]);
    app()->bind('capell.frontend.layout-container-width-resolver', fn (): Closure => fn (?string $default): ContainerWidthEnum => $default === 'lg'
                ? ContainerWidthEnum::Large
                : ContainerWidthEnum::Full);

    expect(GetBlockContainerWidthAction::run($block, ContainerWidthEnum::Large->value))->toBe(ContainerWidthEnum::Large);

    app()->bind('capell.frontend.layout-container-width-resolver', function (): object {
        return new class
        {
            public function resolve(?string $default): ?ContainerWidthEnum
            {
                return $default === null ? ContainerWidthEnum::Medium : null;
            }
        };
    });

    expect(GetBlockContainerWidthAction::run($block))->toBe(ContainerWidthEnum::Medium)
        ->and(GetBlockContainerWidthAction::run($block, ContainerWidthEnum::ExtraLarge->value))->toBe(ContainerWidthEnum::ExtraLarge)
        ->and(FindReusableBlocksAction::run('hero'))->toBe([]);
});

it('detects hero headings from page meta and first block asset translations', function (): void {
    $frontendContext = new LayoutBuilderResidualFrontendContext;
    app()->instance('capell.frontend.context', $frontendContext);

    $page = Page::factory()->withTranslations()->create();
    $page->translation->forceFill([
        'meta' => [
            'hero' => '<section><h1>Welcome</h1></section>',
        ],
    ])->save();
    $page->load('translation');
    $emptyBlock = Widget::factory()->create();
    $emptyBlock->setRelation('assets', new EloquentCollection);

    expect(HeroBlockHasPrimaryHeadingAction::run($emptyBlock, $page))->toBeTrue()
        ->and($frontendContext->data['has_primary_heading'])->toBeTrue();

    $assetPage = Page::factory()->withTranslations()->create();
    $assetPage->translation->forceFill(['title' => 'Asset Heading'])->save();
    $assetPage->load('translation');
    $blockAsset = WidgetAsset::factory()
        ->block(Widget::factory()->create())
        ->asset($assetPage)
        ->make();
    $blockAsset->setRelation('asset', $assetPage->load('translation'));

    $assetBlock = Widget::factory()->create();
    $assetBlock->setRelation('assets', new EloquentCollection([$blockAsset]));

    expect(HeroBlockHasPrimaryHeadingAction::run($assetBlock, $page))->toBeTrue();
});
