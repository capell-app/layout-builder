<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\InvalidateLayoutPreviewImageAction;
use Capell\LayoutBuilder\Enums\LayoutPreviewStatusEnum;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Illuminate\Support\Facades\Storage;

it('invalidates generated preview images using package owned preview metadata', function (): void {
    Storage::fake('public');

    Storage::disk('public')->put('layout-previews/old.png', 'old');

    $layout = Layout::factory()->create([
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'layout-previews/old.png',
            LayoutPreviewMetaKey::SIGNATURE => 'old-signature',
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
            LayoutPreviewMetaKey::ERROR => 'old error',
        ],
        'containers' => [
            'main' => [
                'blocks' => [],
            ],
        ],
    ]);

    expect(InvalidateLayoutPreviewImageAction::run($layout))->toBeTrue();

    $layout->refresh();

    Storage::disk('public')->assertMissing('layout-previews/old.png');

    expect($layout->admin)
        ->toMatchArray([
            LayoutPreviewMetaKey::STATUS => LayoutPreviewStatusEnum::Ready->value,
            LayoutPreviewMetaKey::ERROR => null,
        ])
        ->and($layout->admin[LayoutPreviewMetaKey::IMAGE])->toStartWith('generated-layout-previews/layout-')
        ->and($layout->admin[LayoutPreviewMetaKey::SIGNATURE])->toBeString()
        ->and($layout->admin[LayoutPreviewMetaKey::SIGNATURE])->not->toBe('old-signature');

    Storage::disk('public')->assertExists($layout->admin[LayoutPreviewMetaKey::IMAGE]);
});
