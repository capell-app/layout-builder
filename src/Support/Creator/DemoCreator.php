<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;

class DemoCreator extends ApDemoWidgetCreator
{
    public function __construct(
        protected readonly ?Model $user = null,
    ) {
        throw_unless(CapellCore::hasAsset('Section'), RuntimeException::class, 'Content Sections must be installed to create section demo content.');
        $contentModel = CapellCore::getAsset('Section')->model;

        throw_unless(
            is_subclass_of($contentModel, Model::class) && is_subclass_of($contentModel, HasMedia::class),
            RuntimeException::class,
            'Section asset model must be an Eloquent media model.',
        );

        /** @var class-string<Model&HasMedia> $contentModel */
        $this->contentModel = $contentModel;
        $this->widgetModel = Widget::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }
}
