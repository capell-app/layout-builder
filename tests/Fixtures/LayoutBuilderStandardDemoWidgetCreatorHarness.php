<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use BackedEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\StandardDemoWidgetCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Override;

final class LayoutBuilderStandardDemoWidgetCreatorHarness extends StandardDemoWidgetCreator
{
    public function __construct()
    {
        $this->contentModel = LayoutBuilderStandardDemoContentPage::class;
        $this->widgetModel = Widget::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }

    /**
     * @param  Collection<array-key, mixed>  $siteTree
     * @return array<array-key, mixed>
     */
    public function exposeNavigationPageItems(Collection $siteTree, Language $language): array
    {
        return $this->navigationPageItems($siteTree, $language);
    }

    /**
     * @return Collection<array-key, mixed>
     */
    public function exposeCreateFeatures(Site $site): Collection
    {
        return $this->createFeatures($site);
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @return Collection<array-key, mixed>
     */
    public function exposeCreateTestimonials(Collection $languages): Collection
    {
        return $this->createTestimonials($languages);
    }

    /**
     * @param  Collection<array-key, mixed>  $languages
     * @return Collection<array-key, mixed>
     */
    public function exposeCreateTeamMembers(Collection $languages): Collection
    {
        return $this->createTeamMembers($languages);
    }

    #[Override]
    protected function createMedia(Model $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): void {}

    #[Override]
    protected function createWidgetMedia(Widget $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): Media
    {
        $content = LayoutBuilderStandardDemoContentPage::query()->create([
            'name' => $name ?? 'Demo Media',
        ]);

        $model->assets()->create([
            'asset_id' => $content->getKey(),
            'asset_type' => $content->getMorphClass(),
        ]);

        return Media::factory()->create([
            'model_type' => $content->getMorphClass(),
            'model_id' => $content->getKey(),
            'collection_name' => $collection instanceof BackedEnum ? $collection->value : $collection,
        ]);
    }
}
