<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Actions\GenerateLayoutPreviewImageAction;
use Capell\LayoutBuilder\Actions\GetLayoutPreviewImageUrlAction;
use Capell\LayoutBuilder\Actions\InvalidateLayoutPreviewImageAction;
use Capell\LayoutBuilder\Database\Factories\LayoutFactory;
use Capell\LayoutBuilder\Enums\LayoutPreviewStatusEnum;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewRenderer;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewSignature;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Pest\Expectation;

beforeEach(function (): void {
    Queue::fake();
    Storage::fake('public');
});

it('changes the preview signature when container structure changes', function (): void {
    $widget = Widget::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
        'admin' => ['icon' => 'heroicon-o-sparkles'],
    ]);

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $signature = resolve(LayoutPreviewSignature::class);
    $initialSignature = $signature->forLayout($layout);

    $containers = $layout->containers;
    $containers['main']['meta']['colspan'] = 6;
    $layout->forceFill(['containers' => $containers])->save();

    expect($signature->forLayout($layout->refresh()))->not->toBe($initialSignature);
});

it('changes the preview signature when widget display data changes', function (): void {
    $widget = Widget::factory()->create([
        'key' => 'hero',
        'name' => 'Hero',
    ]);

    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $signature = resolve(LayoutPreviewSignature::class);
    $initialSignature = $signature->forLayout($layout);

    $widget->update(['name' => 'Feature hero']);

    expect($signature->forLayout($layout->refresh()))->not->toBe($initialSignature);
});

it('invalidates the generated preview image and dispatches generation', function (): void {
    Storage::disk('public')->put('generated-layout-previews/old.png', 'old-preview');

    $layout = (new LayoutFactory)->containers()->create([
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'generated-layout-previews/old.png',
            LayoutPreviewMetaKey::SIGNATURE => 'old-signature',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
        ],
    ]);

    $result = InvalidateLayoutPreviewImageAction::run($layout);
    $layout->refresh();

    expect($result)->toBeTrue()
        ->and(Storage::disk('public')->exists('generated-layout-previews/old.png'))->toBeFalse()
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE])->toBeNull()
        ->and($layout->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Pending->value);

    GenerateLayoutPreviewImageAction::assertPushed(
        1,
        fn (GenerateLayoutPreviewImageAction $action, array $arguments): Expectation => expect($arguments[0])->toBe($layout->getKey()),
    );
});

it('writes a generated png preview and marks the layout ready', function (): void {
    $layout = (new LayoutFactory)->containers()->create();
    $signature = resolve(LayoutPreviewSignature::class)->forLayout($layout);
    $layout->forceFill([
        'admin' => [
            LayoutPreviewMetaKey::SIGNATURE => $signature,
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Pending->value,
        ],
    ])->save();

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), $signature);
    $layout->refresh();

    expect($layout->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Ready->value)
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE])->toBeString()
        ->and(Storage::disk('public')->exists($layout->admin[LayoutPreviewMetaKey::IMAGE]))->toBeTrue();
});

it('does not let a stale queued preview overwrite a newer signature', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [],
        'admin' => [
            LayoutPreviewMetaKey::SIGNATURE => 'new-signature',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Pending->value,
        ],
    ]);

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), 'old-signature');
    $layout->refresh();

    expect($layout->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Pending->value)
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE] ?? null)->toBeNull()
        ->and(Storage::disk('public')->files('generated-layout-previews'))->toBe([]);
});

it('marks the preview failed when rendering fails', function (): void {
    app()->bind(LayoutPreviewRenderer::class, fn (): LayoutPreviewRenderer => new class extends LayoutPreviewRenderer
    {
        public function render(Layout $layout): string
        {
            throw new RuntimeException('Renderer unavailable.');
        }
    });

    $layout = (new LayoutFactory)->containers()->create();
    $signature = resolve(LayoutPreviewSignature::class)->forLayout($layout);
    $layout->forceFill([
        'admin' => [
            LayoutPreviewMetaKey::SIGNATURE => $signature,
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Pending->value,
        ],
    ])->save();

    GenerateLayoutPreviewImageAction::run((int) $layout->getKey(), $signature);
    $layout->refresh();

    expect($layout->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Failed->value)
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE])->toBeNull()
        ->and($layout->admin[LayoutPreviewMetaKey::ERROR])->toContain('Renderer unavailable');
});

it('uses manual preview images before generated previews', function (): void {
    $layout = (new LayoutFactory)->containers()->create([
        'admin' => [
            'image' => 'manual-layout-preview.png',
            LayoutPreviewMetaKey::IMAGE => 'generated-layout-previews/generated.png',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
        ],
    ]);

    expect(GetLayoutPreviewImageUrlAction::run($layout))->toContain('manual-layout-preview.png');
});

it('falls back to the generated preview image when there is no manual preview', function (): void {
    $layout = (new LayoutFactory)->containers()->create([
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'generated-layout-previews/generated.png',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
        ],
    ]);

    expect(GetLayoutPreviewImageUrlAction::run($layout))->toContain('generated-layout-previews/generated.png');
});

it('invalidates affected layouts when widget display data changes', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $unrelatedWidget = Widget::factory()->create(['key' => 'footer']);
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $unrelatedLayout = (new LayoutFactory)->widgets([$unrelatedWidget])->create();

    $widget->update(['name' => 'Updated hero']);

    expect($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Pending->value)
        ->and($unrelatedLayout->refresh()->admin[LayoutPreviewMetaKey::STATUS] ?? null)->toBeNull();
});

it('invalidates affected layouts when widget type display data changes', function (): void {
    $type = Type::factory()
        ->type('widget')
        ->create(['name' => 'Promos', 'admin' => ['icon' => 'heroicon-o-bolt']]);
    $widget = Widget::factory()->create(['key' => 'promo', 'type_id' => $type->getKey()]);
    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $type->update(['admin' => ['icon' => 'heroicon-o-sparkles']]);

    expect($layout->refresh()->admin[LayoutPreviewMetaKey::STATUS])->toBe(LayoutPreviewStatusEnum::Pending->value);
});
