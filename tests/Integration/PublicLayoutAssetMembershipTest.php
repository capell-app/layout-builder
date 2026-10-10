<?php

declare(strict_types=1);

use Capell\ContentSections\Models\Section;
use Capell\Core\Database\Factories\TranslationFactory;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Publishing\PublishSentinel;
use Capell\LayoutBuilder\Contracts\PublicLayoutAssetMembership;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\Assets\LayoutBuilderPublicLayoutAssetMembership;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    // This standalone harness omits the optional publishing workspace migration.
    if (! Schema::hasColumn('translations', 'workspace_id')) {
        Schema::table('translations', fn (Blueprint $table) => $table->unsignedBigInteger('workspace_id')->default(0));
    }
});

it('binds the public asset membership contract to its owning implementation', function (): void {
    expect(get_class(resolve(PublicLayoutAssetMembership::class)))->toBe(LayoutBuilderPublicLayoutAssetMembership::class);
});

it('finds a current public attachment without rendering widget payloads', function (): void {
    $fixture = publicLayoutMembershipFixture();
    $renderer = Mockery::mock(PublicLayoutWidgetPayloadResolver::class);
    $renderer->shouldNotReceive('data');
    $renderer->shouldNotReceive('html');
    app()->instance(PublicLayoutWidgetPayloadResolver::class, $renderer);
    $membership = resolve(LayoutBuilderPublicLayoutAssetMembership::class);
    expect($membership->contains($fixture['section'], $fixture['page'], $fixture['layout'], $fixture['language']))->toBeTrue();
});

it('rejects foreign page layout language and asset identities', function (string $foreign): void {
    $fixture = publicLayoutMembershipFixture();
    if ($foreign === 'page') {
        $fixture['page'] = Page::factory()->site($fixture['site'])->layout($fixture['layout'])->withTranslations($fixture['language'])->create();
    } elseif ($foreign === 'layout') {
        $fixture['layout'] = Layout::factory()->site($fixture['site'])->create(['containers' => $fixture['layout']->containers]);
    } elseif ($foreign === 'language') {
        $fixture['language'] = Language::factory()->create();
    } else {
        $fixture['section'] = Section::factory()->site($fixture['site'])->withTranslations($fixture['language'])->create();
    }
    expect(resolve(LayoutBuilderPublicLayoutAssetMembership::class)->contains($fixture['section'], $fixture['page'], $fixture['layout'], $fixture['language']))->toBeFalse();
})->with(['page', 'layout', 'language', 'asset']);

it('rejects detached unpublished and disabled current attachments', function (string $state): void {
    $fixture = publicLayoutMembershipFixture();
    if ($state === 'detached') {
        $fixture['attachment']->delete();
    } elseif ($state === 'unpublished') {
        $fixture['section']->update(['visible_from' => PublishSentinel::draftValue()]);
    } else {
        $fixture['widget']->update(['status' => false]);
    }
    app()->forgetInstance(LayoutLoader::class);
    expect(resolve(LayoutBuilderPublicLayoutAssetMembership::class)->contains($fixture['section'], $fixture['page'], $fixture['layout'], $fixture['language']))->toBeFalse();
})->with(['detached', 'unpublished', 'disabled']);

it('rechecks attachments with the current loader on the same membership service', function (): void {
    $fixture = publicLayoutMembershipFixture();
    $membership = resolve(PublicLayoutAssetMembership::class);
    expect($membership->contains($fixture['section'], $fixture['page'], $fixture['layout'], $fixture['language']))->toBeTrue();
    $fixture['attachment']->delete();
    app()->forgetInstance(LayoutLoader::class);
    expect($membership->contains($fixture['section'], $fixture['page'], $fixture['layout'], $fixture['language']))->toBeFalse();
});

/** @return array{language: Language, site: Site, widget: Widget, layout: Layout, page: Page, section: Section, attachment: WidgetAsset} */
function publicLayoutMembershipFixture(): array
{
    $language = Language::factory()->create(['status' => true]);
    $site = Site::factory()->language($language)->withTranslations($language)->create(['status' => true]);
    $widget = Widget::factory()->create(['key' => 'membership-test-widget', 'status' => true]);
    TranslationFactory::new()->translatable($widget)->language($language)->create(['title' => 'Widget']);
    $layout = Layout::factory()->site($site)->create(['status' => true, 'containers' => ['main' => ['widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]]]]]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create(['visible_from' => now()->subDay(), 'visible_until' => null]);
    $section = Section::factory()->site($site)->withTranslations($language)->create(['visible_from' => now()->subDay(), 'visible_until' => null, 'workspace_id' => 0]);
    $attachment = WidgetAsset::query()->create(['widget_id' => $widget->getKey(), 'pageable_type' => $page->getMorphClass(), 'pageable_id' => $page->getKey(), 'container' => 'main', 'occurrence' => 1, 'workspace_id' => 0, 'asset_type' => $section->getMorphClass(), 'asset_id' => $section->getKey()]);

    return compact('language', 'site', 'widget', 'layout', 'page', 'section', 'attachment');
}
