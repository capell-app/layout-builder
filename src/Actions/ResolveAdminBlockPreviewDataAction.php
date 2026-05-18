<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\LayoutBuilder\Data\AdminBlockPreviewData;
use Capell\LayoutBuilder\Enums\BlockComponentEnum;
use Capell\LayoutBuilder\Models\Block;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ResolveAdminBlockPreviewDataAction
{
    use AsObject;

    /**
     * @param  array<string, mixed>  $containerBlock
     */
    public function handle(
        Block $block,
        array $containerBlock,
        ?Pageable $page,
        int $assetCount,
        bool $hasPageAssets,
    ): AdminBlockPreviewData {
        $usesPageContent = $this->isPageContentBlock($block);
        $title = $this->title($block, $containerBlock, $page, $usesPageContent);
        $excerpt = $this->excerpt($block, $page, $usesPageContent);
        $image = $this->image($block, $page, $usesPageContent);

        return new AdminBlockPreviewData(
            view: $this->view($block, $usesPageContent),
            label: $this->label($block, $containerBlock),
            title: $title,
            excerpt: $excerpt,
            image: $image,
            typeLabel: $this->typeLabel($block),
            icon: $this->icon($block),
            assetCount: $assetCount,
            hasPageAssets: $hasPageAssets,
            usesPageContent: $usesPageContent,
        );
    }

    private function view(Block $block, bool $usesPageContent): string
    {
        $customView = Arr::get($block->admin ?? [], 'admin_preview_view')
            ?? Arr::get($block->type?->admin ?? [], 'admin_preview_view');

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
     * @param  array<string, mixed>  $containerBlock
     */
    private function label(Block $block, array $containerBlock): string
    {
        $layoutName = Arr::get($containerBlock, 'meta.name');

        if (is_string($layoutName) && $layoutName !== '') {
            return $layoutName;
        }

        return $block->name;
    }

    /**
     * @param  array<string, mixed>  $containerBlock
     */
    private function title(Block $block, array $containerBlock, ?Pageable $page, bool $usesPageContent): ?string
    {
        if ($usesPageContent && $page instanceof Pageable) {
            return $page->translation?->title !== null && $page->translation->title !== ''
                ? $page->translation->title
                : $page->name;
        }

        $layoutName = Arr::get($containerBlock, 'meta.name');

        if (is_string($layoutName) && $layoutName !== '') {
            return $layoutName;
        }

        return $block->translation?->title;
    }

    private function excerpt(Block $block, ?Pageable $page, bool $usesPageContent): ?string
    {
        $content = $usesPageContent && $page instanceof Pageable
            ? $page->translation?->content
            : $block->translation?->content;

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

    private function image(Block $block, ?Pageable $page, bool $usesPageContent): ?Media
    {
        if ($usesPageContent && $page instanceof Pageable && $page->image instanceof Media) {
            return $page->image;
        }

        $image = $this->loadedRelation($block, 'image');

        if ($image instanceof Media) {
            return $image;
        }

        $backgroundImage = $this->loadedRelation($block, 'backgroundImage');

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

    private function typeLabel(Block $block): ?string
    {
        $type = Arr::get($block->admin ?? [], 'type')
            ?? Arr::get($block->type?->admin ?? [], 'type');

        return is_string($type) && $type !== '' ? $type : null;
    }

    private function icon(Block $block): ?string
    {
        $icon = Arr::get($block->admin ?? [], 'icon')
            ?? Arr::get($block->type?->admin ?? [], 'icon');

        return is_string($icon) && $icon !== '' ? $icon : null;
    }

    private function isPageContentBlock(Block $block): bool
    {
        return $block->getMetaComponent() === BlockComponentEnum::PageContent->value;
    }
}
