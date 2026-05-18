<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Creator;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Element;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class DemoCreator extends ApDemoElementCreator
{
    public function __construct(
        protected readonly ?Model $user = null,
    ) {
        throw_unless(CapellCore::hasAsset('Section'), RuntimeException::class, 'Content Sections must be installed to create section demo content.');
        $this->contentModel = CapellCore::getAsset('Section')->model;
        $this->elementModel = Element::class;
        $this->typeModel = Blueprint::class;
        $this->pageModel = Page::class;
    }
}
