<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Fragments;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Actions\Fragments\ResolvePublicFragmentContentVersionAction;
use Capell\Frontend\Contracts\Fragments\PublicFragmentReferenceCodec;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Facades\Frontend;
use Capell\LayoutBuilder\Fragments\LayoutBuilderFragmentUrlResolver;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildLayoutBuilderFragmentReferenceAction
{
    use AsObject;

    public function handle(
        string $containerKey,
        int $occurrence,
        Widget $widget,
    ): ?string {
        $page = Frontend::page();
        $site = Frontend::site();
        $language = Frontend::language();
        $layout = Frontend::layout();
        $widgetKey = $widget->getAttribute('key');

        if (! $page instanceof Page
            || ! $site instanceof Site
            || ! $language instanceof Language
            || ! $layout instanceof Layout
            || ! is_string($widgetKey)
            || $widgetKey === '') {
            return null;
        }

        $ownerContext = $this->ownerContext(
            $layout,
            $widget,
            $page,
            $language,
            $containerKey,
            $widgetKey,
            max(1, $occurrence),
        );

        $reference = new PublicFragmentReferenceData(
            owner: LayoutBuilderFragmentUrlResolver::OWNER,
            formatVersion: 1,
            pageableType: $page->getMorphClass(),
            pageableId: $this->scalarKey($page),
            siteId: $this->scalarKey($site),
            languageId: $this->scalarKey($language),
            contentVersion: ResolvePublicFragmentContentVersionAction::make()->handle(
                $page,
                $site,
                $language,
                $layout,
                $ownerContext,
            ),
            ownerContext: $ownerContext,
        );

        return resolve(PublicFragmentReferenceCodec::class)->encode($reference);
    }

    /**
     * @return array<string, int|string>
     */
    private function ownerContext(
        Layout $layout,
        Widget $widget,
        Page $page,
        Language $language,
        string $containerKey,
        string $widgetKey,
        int $occurrence,
    ): array {
        return [
            'layoutId' => $this->scalarKey($layout),
            'containerKey' => $containerKey,
            'widgetKey' => $widgetKey,
            'occurrence' => $occurrence,
            'widgetVersion' => ResolveLayoutBuilderFragmentWidgetVersionAction::make()->handle(
                $widget,
                $page,
                $language,
                $containerKey,
                $occurrence,
            ),
        ];
    }

    private function scalarKey(Model $model): int|string
    {
        $key = $model->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new LogicException('Public fragment context records require scalar identifiers.');
        }

        return $key;
    }
}
