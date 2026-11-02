<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\WidgetYouTube\Filament\YouTubeWidget;
use Capell\WidgetYouTube\Providers\WidgetYouTubeServiceProvider;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Support\WidgetScreenshotFixtures;

uses(CreatesAdminUser::class);

/** State path of the YouTube block's field in the page editor, e.g. data.translations.record-1.content.<uuid>.data.videoId. */
function youtubeEditorFieldPath(Testable $editor, string $field): string
{
    $page = $editor->instance();
    if (! $page instanceof EditPage) {
        throw new RuntimeException('Expected the page editor.');
    }

    $form = $page->getSchema('form');
    if (! $form instanceof Schema) {
        throw new RuntimeException('Expected the page editor form.');
    }

    $uploads = array_filter($form->getFlatFields(withHidden: true), static fn (Field $candidate): bool => $candidate instanceof FileUpload && str_contains((string) $candidate->getStatePath(), '.content.'));
    if ($uploads === []) {
        throw new RuntimeException('Expected the YouTube poster upload.');
    }

    return str_replace('.poster', '.' . $field, (string) array_values($uploads)[0]->getStatePath());
}

beforeEach(function (): void {
    CapellCore::forcePackageInstalled('capell-app/widget-youtube');
    $this->app->register(WidgetYouTubeServiceProvider::class);
    resolve(WidgetDiscovery::class)->registerAuthoritative(YouTubeWidget::class);
    config()->set('filament.default_filesystem_disk', 'public');
    Storage::fake('public');
    Storage::disk('public')->put('widget-screenshots/workspace.jpg', UploadedFile::fake()->image('workspace.jpg')->getContent());
    $this->actingAsAdmin();
    $this->page = Page::factory()->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations(data: ['content' => json_encode(WidgetScreenshotFixtures::blocks('youtube'), JSON_THROW_ON_ERROR)], contentStructure: ContentStructure::Blocks)
        ->create();
});

it('shows the saved video as a url when the page is reopened', function (): void {
    $editor = Livewire::test(EditPage::class, ['record' => $this->page->getRouteKey()]);

    expect($editor->get(youtubeEditorFieldPath($editor, 'videoId')))->toBe('https://www.youtube.com/watch?v=aqz-KE-bpKQ');
});

it('keeps the saved video when only the caption changes and the field still holds the stored id', function (): void {
    $editor = Livewire::test(EditPage::class, ['record' => $this->page->getRouteKey()]);
    // A builder that restores stored state without running field formatters leaves the bare id in the input.
    $editor->set(youtubeEditorFieldPath($editor, 'videoId'), 'aqz-KE-bpKQ')
        ->set(youtubeEditorFieldPath($editor, 'caption'), 'Second caption')
        ->call('save')
        ->assertHasNoErrors();

    $block = $this->page->translations()->sole()->content[0]['data'];
    expect($block['videoId'])->toBe('aqz-KE-bpKQ')
        ->and($block['caption'])->toBe('Second caption');
});

it('rejects unsupported values with the package message and preserves the saved video', function (string $value): void {
    $editor = Livewire::test(EditPage::class, ['record' => $this->page->getRouteKey()]);
    $path = youtubeEditorFieldPath($editor, 'videoId');
    $editor->set($path, $value)->call('save')
        ->assertHasErrors([$path => __('capell-widget-youtube::widget.invalid_url')]);

    expect($this->page->translations()->sole()->content[0]['data']['videoId'])->toBe('aqz-KE-bpKQ');
})->with(['https://vimeo.com/123456789', 'https://youtube.com/watch?v=broken', 'broken', 'aqz-KE-bpKQ-extra', 'javascript:alert(1)']);
