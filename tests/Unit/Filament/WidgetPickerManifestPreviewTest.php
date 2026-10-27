<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Support\Manifest\CapellManifestData;
use Capell\LayoutBuilder\Actions\ResolveWidgetPickerPreviewAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\RenderedDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

function writePreviewIndex(string $root, string $path, string $widget = 'preview-fixture.example'): void
{
    File::put($root . '/resources/previews/previews.json', json_encode(['previews' => [['widget' => $widget, 'path' => $path]]], JSON_THROW_ON_ERROR));
}

beforeEach(function (): void {
    $this->previewRoot = sys_get_temp_dir() . '/capell-widget-picker-' . bin2hex(random_bytes(8));
    File::makeDirectory($this->previewRoot . '/resources/previews', 0755, true);
    $image = imagecreatetruecolor(480, 300);
    throw_unless($image instanceof GdImage, RuntimeException::class);
    imagewebp($image, $this->previewRoot . '/resources/previews/example.webp');
    imagedestroy($image);

    $manifest = InstalledRuntimeParity::manifest('layout-builder');
    $manifest['name'] = 'preview-fixture/widgets';
    $manifest['slug'] = 'preview-fixture-widgets';
    $manifest['contributes'] = [];
    writePreviewIndex($this->previewRoot, 'resources/previews/example.webp');
    CapellCore::registerManifestPackage(CapellManifestData::fromArray($manifest, $this->previewRoot));
    CapellCore::forcePackageInstalled('preview-fixture/widgets');
});

afterEach(function (): void {
    File::deleteDirectory($this->previewRoot);
});

it('renders a declared package preview in the actual picker card without exposing package state', function (): void {
    $widget = new Widget([
        'name' => 'Sample gallery',
        'key' => 'private-record-key',
        'component' => 'preview-fixture.example',
        'status' => true,
        'admin' => ['notes' => 'private-editor-notes'],
    ]);
    $widget->setRelation('image', null);
    $widget->setRelation('blueprint', null);

    $html = view('capell-layout-builder::filament.resources.widgets.widget-card', [
        'getRecord' => fn (): Widget => $widget,
    ])->render();
    $document = new RenderedDocument($html);
    $image = $document->element('//article//img');
    $bytes = File::get($this->previewRoot . '/resources/previews/example.webp');

    expect($image->getAttribute('src'))->toBe('data:image/webp;base64,' . base64_encode($bytes))
        ->and($image->getAttribute('alt'))->toBe(__('capell-layout-builder::table.widget_preview_alt', ['widget' => $widget->name]))
        ->and($html)->not->toContain('private-record-key', 'private-editor-notes', 'preview-fixture.example', $this->previewRoot);
});

it('keeps uploaded and generated record images ahead of package previews', function (string $key): void {
    $widget = new Widget([
        'name' => 'Gallery',
        'component' => 'preview-fixture.example',
        'admin' => [$key => 'owned-preview.webp'],
    ]);
    $widget->setRelation('image', null);

    expect(ResolveWidgetPickerPreviewAction::run($widget)->imageUrl)->toEndWith('/owned-preview.webp');
})->with(['image', 'generated_preview_image']);

it('uses loaded blueprint components for existing widgets without changing stored state', function (): void {
    $blueprint = new Blueprint(['component' => 'preview-fixture.example']);
    $widget = new Widget(['name' => 'Gallery', 'status' => false]);
    $widget->setRelation('blueprint', $blueprint);

    $attributes = $widget->getAttributes();

    $preview = ResolveWidgetPickerPreviewAction::run($widget);

    expect($preview->imageUrl)->toStartWith('data:image/webp;base64,')
        ->and($preview->disabled)->toBeTrue()
        ->and($widget->getAttributes())->toBe($attributes)
        ->and($widget->relationLoaded('image'))->toBeFalse();
});

it('ignores unavailable packages', function (): void {
    CapellCore::forcePackageInstalled('preview-fixture/widgets', false);
    $widget = new Widget(['component' => 'preview-fixture.example']);

    expect(ResolveWidgetPickerPreviewAction::run($widget)->imageUrl)->toBeNull();
});

it('does not fetch unloaded widget relationships while resolving picker data', function (): void {
    $widget = new Widget(['name' => 'Custom widget', 'admin' => ['icon' => 'missing-icon']]);
    // Warm the public package registry independently of the card's relation contract.
    CapellCore::getInstalledPackages();
    DB::enableQueryLog();
    DB::flushQueryLog();
    try {
        $preview = ResolveWidgetPickerPreviewAction::run($widget);
        expect(DB::getQueryLog())->toBe([])
            ->and($preview->imageUrl)->toBeNull()
            ->and($preview->icon)->toBe('heroicon-o-squares-2x2');
    } finally {
        DB::disableQueryLog();
    }
});

it('rejects missing, oversized, non-image and escaping preview files', function (string $case): void {
    $path = match ($case) {
        'missing' => 'resources/previews/missing.webp',
        'traversal' => 'resources/previews/../../../outside.webp',
        'symlink' => 'resources/previews/link.webp',
        'svg' => 'resources/previews/unsafe.svg',
        'oversized' => 'resources/previews/oversized.webp',
        'text' => 'resources/previews/text.webp',
        default => throw new InvalidArgumentException('Unknown preview file scenario.'),
    };
    File::copy($this->previewRoot . '/resources/previews/example.webp', $this->previewRoot . '/outside.webp');
    symlink($this->previewRoot . '/outside.webp', $this->previewRoot . '/resources/previews/link.webp');
    File::put($this->previewRoot . '/resources/previews/unsafe.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    File::put($this->previewRoot . '/resources/previews/oversized.webp', str_repeat('x', 512 * 1024 + 1));
    File::put($this->previewRoot . '/resources/previews/text.webp', 'Private content');
    writePreviewIndex($this->previewRoot, $path);
    $widget = new Widget(['name' => 'Gallery', 'component' => 'preview-fixture.example']);

    expect(ResolveWidgetPickerPreviewAction::run($widget)->imageUrl)->toBeNull();
})->with(['missing', 'traversal', 'symlink', 'svg', 'oversized', 'text']);

it('ignores previews declared for another widget or in a malformed index', function (string $case): void {
    match ($case) {
        'unrelated' => writePreviewIndex($this->previewRoot, 'resources/previews/example.webp', 'another.component'),
        'malformed' => File::put($this->previewRoot . '/resources/previews/previews.json', '{not json'),
        'shape' => File::put($this->previewRoot . '/resources/previews/previews.json', '{"previews": "example.webp"}'),
        default => throw new InvalidArgumentException('Unknown preview index scenario.'),
    };

    expect(ResolveWidgetPickerPreviewAction::run(new Widget(['component' => 'preview-fixture.example']))->imageUrl)->toBeNull();
})->with(['unrelated', 'malformed', 'shape']);
