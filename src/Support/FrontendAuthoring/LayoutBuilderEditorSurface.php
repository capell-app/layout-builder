<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\FrontendAuthoring;

use Capell\Core\Contracts\Pageable;
use Capell\FrontendAuthoring\Contracts\EditableRegionEditorSurface;
use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;
use Capell\FrontendAuthoring\Enums\EditableRegionSurface;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

final class LayoutBuilderEditorSurface implements EditableRegionEditorSurface
{
    public function surface(): EditableRegionSurface
    {
        return EditableRegionSurface::LayoutBuilder;
    }

    public function render(EditableRegionPayloadData $payload, AuthenticatableContract $user): View
    {
        $context = $payload->context;
        $pageClass = $context['pageClass'] ?? null;

        abort_unless(is_string($pageClass) && is_a($pageClass, Model::class, true) && is_a($pageClass, Pageable::class, true), 403);

        return resolve(Factory::class)->make('capell-layout-builder::frontend-authoring.layout-builder-editor', [
            'title' => $payload->label,
            'description' => $payload->description,
            'layoutId' => $this->integerContext($context, 'layoutId'),
            'siteId' => $this->integerContext($context, 'siteId'),
            'pageId' => $this->integerContext($context, 'pageId'),
            'pageClass' => $pageClass,
            'initialContainerKey' => isset($context['initialContainerKey']) ? (string) $context['initialContainerKey'] : null,
            'initialWidgetIndex' => isset($context['initialWidgetIndex']) && is_numeric($context['initialWidgetIndex'])
                ? (int) $context['initialWidgetIndex']
                : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function integerContext(array $context, string $key): int
    {
        $value = $context[$key] ?? null;

        abort_unless(is_numeric($value) && (int) $value > 0, 403);

        return (int) $value;
    }
}
