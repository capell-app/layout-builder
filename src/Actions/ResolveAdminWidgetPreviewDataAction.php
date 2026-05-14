<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Admin\Data\LayoutBuilder\AdminWidgetPreviewData;
use Capell\Core\Contracts\Pageable;
use Capell\Core\LayoutBuilder\Enums\WidgetComponentEnum;
use Capell\Core\Models\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ResolveAdminWidgetPreviewDataAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $containerWidget
     */
    public function handle(
        Widget $widget,
        array $containerWidget,
        ?Pageable $page,
        int $assetCount,
        bool $hasPageAssets,
    ): AdminWidgetPreviewData {
        $usesPageContent = $this->isPageContentWidget($widget);
        $title = $this->title($widget, $containerWidget, $page, $usesPageContent);
        $excerpt = $this->excerpt($widget, $page, $usesPageContent);
        $image = $this->image($widget, $page, $usesPageContent);

        return new AdminWidgetPreviewData(
            view: $this->view($widget, $usesPageContent),
            label: $this->label($widget, $containerWidget),
            title: $title,
            excerpt: $excerpt,
            image: $image,
            typeLabel: $this->typeLabel($widget),
            icon: $this->icon($widget),
            assetCount: $assetCount,
            hasPageAssets: $hasPageAssets,
            usesPageContent: $usesPageContent,
        );
    }

    private function view(Widget $widget, bool $usesPageContent): string
    {
        $customView = Arr::get($widget->admin ?? [], 'admin_preview_view')
            ?? Arr::get($widget->type?->admin ?? [], 'admin_preview_view');

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
     * @param  array<string, mixed>  $containerWidget
     */
    private function label(Widget $widget, array $containerWidget): string
    {
        $layoutName = Arr::get($containerWidget, 'meta.name');

        if (is_string($layoutName) && $layoutName !== '') {
            return $layoutName;
        }

        return $widget->name;
    }

    /**
     * @param  array<string, mixed>  $containerWidget
     */
    private function title(Widget $widget, array $containerWidget, ?Pageable $page, bool $usesPageContent): ?string
    {
        if ($usesPageContent && $page instanceof Pageable) {
            return $page->translation?->title !== null && $page->translation->title !== ''
                ? $page->translation->title
                : $page->name;
        }

        $layoutName = Arr::get($containerWidget, 'meta.name');

        if (is_string($layoutName) && $layoutName !== '') {
            return $layoutName;
        }

        return $widget->translation?->title;
    }

    private function excerpt(Widget $widget, ?Pageable $page, bool $usesPageContent): ?string
    {
        $content = $usesPageContent && $page instanceof Pageable
            ? $page->translation?->content
            : $widget->translation?->content;

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

    private function image(Widget $widget, ?Pageable $page, bool $usesPageContent): ?Media
    {
        if ($usesPageContent && $page instanceof Pageable && $page->image instanceof Media) {
            return $page->image;
        }

        $image = $this->loadedRelation($widget, 'image');

        if ($image instanceof Media) {
            return $image;
        }

        $backgroundImage = $this->loadedRelation($widget, 'backgroundImage');

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

    private function typeLabel(Widget $widget): ?string
    {
        $type = Arr::get($widget->admin ?? [], 'type')
            ?? Arr::get($widget->type?->admin ?? [], 'type');

        return is_string($type) && $type !== '' ? $type : null;
    }

    private function icon(Widget $widget): ?string
    {
        $icon = Arr::get($widget->admin ?? [], 'icon')
            ?? Arr::get($widget->type?->admin ?? [], 'icon');

        return is_string($icon) && $icon !== '' ? $icon : null;
    }

    private function isPageContentWidget(Widget $widget): bool
    {
        return $widget->getMetaComponent() === WidgetComponentEnum::PageContent->value;
    }
}
