<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\LayoutBuilder\Contracts\PublicWidgetPayloadContributor as CorePublicWidgetPayloadContributor;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadResolver;

class DefaultPublicWidgetPayloadResolver implements PublicWidgetPayloadResolver
{
    /**
     * @return array<string, mixed>
     */
    public function data(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): array
    {
        $data = [
            'title' => $widget->translation?->title,
            'content' => $widget->translation?->content,
        ];

        foreach ($this->contributors() as $contributor) {
            $data = array_replace_recursive(
                $data,
                $contributor->data($widget, $page, $language, $containerKey, $occurrence),
            );
        }

        return $data;
    }

    public function html(Widget $widget, Page $page, Language $language, string $containerKey, int $occurrence): ?string
    {
        $html = collect($this->contributors())
            ->map(fn (PublicWidgetPayloadContributor|CorePublicWidgetPayloadContributor $contributor): ?string => $contributor->html($widget, $page, $language, $containerKey, $occurrence))
            ->filter(fn (?string $html): bool => is_string($html) && trim($html) !== '')
            ->implode("\n");

        return $html === '' ? null : $html;
    }

    /**
     * @return array<int, PublicWidgetPayloadContributor|CorePublicWidgetPayloadContributor>
     */
    private function contributors(): array
    {
        return collect(app()->tagged(PublicWidgetPayloadContributor::TAG))
            ->filter(fn (mixed $contributor): bool => $contributor instanceof PublicWidgetPayloadContributor || $contributor instanceof CorePublicWidgetPayloadContributor)
            ->sortBy(fn (PublicWidgetPayloadContributor|CorePublicWidgetPayloadContributor $contributor): int => $contributor->priority())
            ->values()
            ->all();
    }
}
