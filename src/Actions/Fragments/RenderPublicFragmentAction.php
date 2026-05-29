<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Fragments;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Actions\AssertPublicHtmlContainsNoAuthoringSurfaceAction;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Data\PublicLayoutBlockData;
use Capell\LayoutBuilder\Data\PublicLayoutContainerData;
use Capell\LayoutBuilder\Support\Livewire\OpaqueBlockReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Response;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

class RenderPublicFragmentAction
{
    use AsObject;

    public function handle(string $reference): ?string
    {
        try {
            return $this->render($reference);
        } catch (Throwable) {
            return null;
        }
    }

    private function render(string $reference): ?string
    {
        $data = OpaqueBlockReference::decode($reference);

        $site = $this->model(Site::class, $data['site_id'] ?? null);
        $layout = $this->model(Layout::class, $data['layout_id'] ?? null);
        $language = $this->model(Language::class, $data['language_id'] ?? null);
        $page = $this->page($data['page_type'] ?? null, $data['page_id'] ?? null);
        $containerKey = $this->stringValue($data['container_key'] ?? null);
        $widgetKey = $this->stringValue($data['widget_key'] ?? null);
        $occurrence = $this->positiveInteger($data['occurrence'] ?? null);

        if (! $site instanceof Site
            || ! $layout instanceof Layout
            || ! $language instanceof Language
            || ! $page instanceof Page
            || $containerKey === null
            || $widgetKey === null
            || $occurrence === null) {
            return null;
        }

        if ((int) $page->site_id !== (int) $site->getKey()) {
            return null;
        }

        if ((int) $layout->getKey() !== (int) $page->layout_id) {
            return null;
        }

        if ($layout->site_id !== null && (int) $layout->site_id !== (int) $site->getKey()) {
            return null;
        }

        $graph = BuildPublicLayoutGraphAction::run($layout, $page, $language, [$containerKey], includeHtml: true);
        $container = null;

        foreach ($graph->containers as $candidateContainer) {
            if ($candidateContainer->key === $containerKey) {
                $container = $candidateContainer;

                break;
            }
        }

        $block = null;

        if ($container instanceof PublicLayoutContainerData) {
            foreach ($container->blocks as $candidateBlock) {
                if ($candidateBlock->key === $widgetKey && $candidateBlock->occurrence === $occurrence) {
                    $block = $candidateBlock;

                    break;
                }
            }
        }

        if (! $block instanceof PublicLayoutBlockData || ! is_string($block->html) || trim($block->html) === '') {
            return null;
        }

        $response = new Response($block->html);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        AssertPublicHtmlContainsNoAuthoringSurfaceAction::run($response);

        return $block->html;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @return TModel|null
     */
    private function model(string $model, mixed $id): ?Model
    {
        if (! is_numeric($id)) {
            return null;
        }

        return $model::query()->find((int) $id);
    }

    private function page(mixed $pageType, mixed $id): ?Pageable
    {
        if (! is_numeric($id)) {
            return null;
        }

        $pageClass = is_string($pageType) ? Relation::getMorphedModel($pageType) : null;
        $pageClass ??= Page::class;

        if (! is_a($pageClass, Pageable::class, true)) {
            return null;
        }

        $page = $pageClass::query()->find((int) $id);

        return $page instanceof Pageable ? $page : null;
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function positiveInteger(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max(1, (int) $value);
    }
}
