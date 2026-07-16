<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Assets;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Page;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Actions\LayoutWidgets\BuildLayoutWidgetResourceUsagesAction;
use Capell\LayoutBuilder\Contracts\Assets\LayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;

class PageContentLayoutWidgetResourceUsageContributor implements LayoutWidgetResourceUsageContributor
{
    /**
     * @return array<int, LayoutWidgetResourceUsageData>
     */
    public function usages(FrontendRenderContextData $context): array
    {
        $page = $context->page;

        if (! $page instanceof Pageable || ! $page instanceof Page) {
            return [];
        }

        $type = $page->relationLoaded('blueprint') ? $page->blueprint : null;
        if (($type->content_structure ?? null) !== ContentStructure::Blocks) {
            return [];
        }

        $translation = $page->relationLoaded('translation') ? $page->translation : null;
        $content = $translation?->content;

        if (! is_array($content)) {
            return [];
        }

        return BuildLayoutWidgetResourceUsagesAction::run($content, LayoutWidgetTarget::FrontendBlade);
    }
}
