<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\GetLayoutPreviewImageUrlAction;
use Capell\LayoutBuilder\Support\LayoutPreviews\LayoutPreviewMetaKey;
use Illuminate\Support\Facades\Storage;

it('resolves generated layout preview images from package owned meta keys', function (): void {
    Storage::fake('public');

    $layout = Layout::factory()->create([
        'admin' => [
            LayoutPreviewMetaKey::IMAGE => 'layout-previews/example.png',
        ],
    ]);

    expect(GetLayoutPreviewImageUrlAction::run($layout))
        ->toBe(Storage::disk('public')->url('layout-previews/example.png'));
});
