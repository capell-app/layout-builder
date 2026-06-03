<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use BackedEnum;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\ApDemoWidgetCreator;
use Illuminate\Database\Eloquent\Model;
use Override;

final class LayoutBuilderDemoWidgetCreatorHarness extends ApDemoWidgetCreator
{
    public function __construct()
    {
        $this->contentModel = LayoutBuilderDemoContentPage::class;
        $this->widgetModel = Widget::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }

    #[Override]
    protected function createMedia(Model $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): void {}

    #[Override]
    protected function createWidgetMedia(Widget $model, ?string $name = null, string $type = 'image', BackedEnum|string $collection = 'image'): Media
    {
        return Media::factory()->create([
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'collection_name' => is_string($collection) ? $collection : $collection->value,
        ]);
    }
}
