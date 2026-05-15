<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Admin\Data\LayoutBuilder\AdminWidgetPreviewData;
use Capell\Core\Contracts\Pageable;
use Capell\LayoutBuilder\Enums\ElementComponentEnum;
use Capell\LayoutBuilder\Models\Element;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ResolveAdminElementPreviewDataAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $containerElement
     */
    public function handle(
        Element $element,
        array $containerElement,
        ?Pageable $page,
        int $assetCount,
        bool $hasPageAssets,
    ): AdminWidgetPreviewData {
        $usesPageContent = $this->isPageContentElement($element);
        $title = $this->title($element, $containerElement, $page, $usesPageContent);
        $excerpt = $this->excerpt($element, $page, $usesPageContent);
        $image = $this->image($element, $page, $usesPageContent);

        return new AdminWidgetPreviewData(
            view: $this->view($element, $usesPageContent),
            label: $this->label($element, $containerElement),
            title: $title,
            excerpt: $excerpt,
            image: $image,
            typeLabel: $this->typeLabel($element),
            icon: $this->icon($element),
            assetCount: $assetCount,
            hasPageAssets: $hasPageAssets,
            usesPageContent: $usesPageContent,
        );
    }

    private function view(Element $element, bool $usesPageContent): string
    {
        $customView = Arr::get($element->admin ?? [], 'admin_preview_view')
            ?? Arr::get($element->type?->admin ?? [], 'admin_preview_view');

        if (is_string($customView) && $customView !== '' && $this->isPreviewView($customView)) {
            return $customView;
        }

        if ($usesPageContent) {
            return 'capell-layout-builder::filament.layout-builder.previews.page-content';
        }

        return 'capell-layout-builder::filament.layout-builder.previews.default';
    }

    private function isPreviewView(string $view): bool
    {
        return str($view)->contains('::filament.layout-builder.previews.');
    }

    /**
     * @param  array<string, mixed>  $containerElement
     */
    private function label(Element $element, array $containerElement): string
    {
        $layoutName = Arr::get($containerElement, 'meta.name');

        if (is_string($layoutName) && $layoutName !== '') {
            return $layoutName;
        }

        return $element->name;
    }

    /**
     * @param  array<string, mixed>  $containerElement
     */
    private function title(Element $element, array $containerElement, ?Pageable $page, bool $usesPageContent): ?string
    {
        if ($usesPageContent && $page instanceof Pageable) {
            return $page->translation?->title !== null && $page->translation->title !== ''
                ? $page->translation->title
                : $page->name;
        }

        $layoutName = Arr::get($containerElement, 'meta.name');

        if (is_string($layoutName) && $layoutName !== '') {
            return $layoutName;
        }

        return $element->translation?->title;
    }

    private function excerpt(Element $element, ?Pageable $page, bool $usesPageContent): ?string
    {
        $content = $usesPageContent && $page instanceof Pageable
            ? $page->translation?->content
            : $element->translation?->content;

        if (is_array($content)) {
            $content = collect($content)
                ->flatten()
                ->filter(fn (mixed $value): bool => is_scalar($value))
                ->implode(' ');
        }

        if (! is_string($content) || trim($content) === '') {
            return null;
        }

        return str(strip_tags($content))->squish()->limit(180)->toString();
    }

    private function image(Element $element, ?Pageable $page, bool $usesPageContent): ?Media
    {
        if ($usesPageContent && $page instanceof Pageable && $page->image instanceof Media) {
            return $page->image;
        }

        $image = $this->loadedRelation($element, 'image');

        if ($image instanceof Media) {
            return $image;
        }

        $backgroundImage = $this->loadedRelation($element, 'backgroundImage');

        if ($backgroundImage instanceof Media) {
            return $backgroundImage;
        }

        return null;
    }

    private function loadedRelation(Model $model, string $relation): mixed
    {
        if (! $model->relationLoaded($relation)) {
            return null;
        }

        return $model->getRelation($relation);
    }

    private function typeLabel(Element $element): ?string
    {
        $type = Arr::get($element->admin ?? [], 'type')
            ?? Arr::get($element->type?->admin ?? [], 'type');

        return is_string($type) && $type !== '' ? $type : null;
    }

    private function icon(Element $element): ?string
    {
        $icon = Arr::get($element->admin ?? [], 'icon')
            ?? Arr::get($element->type?->admin ?? [], 'icon');

        return is_string($icon) && $icon !== '' ? $icon : null;
    }

    private function isPageContentElement(Element $element): bool
    {
        return $element->getMetaComponent() === ElementComponentEnum::PageContent->value;
    }
}
