<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicWidgetPayloadResolver;
use Capell\LayoutBuilder\Models\Widget;

class DefaultPublicWidgetPayloadResolver implements PublicWidgetPayloadResolver
{
    /**
     * @var array<int, PublicWidgetPayloadContributor>|null
     */
    private ?array $contributors = null;

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
            ->map(fn (PublicWidgetPayloadContributor $contributor): ?string => $contributor->html($widget, $page, $language, $containerKey, $occurrence))
            ->filter(fn (?string $html): bool => is_string($html) && trim($html) !== '')
            ->implode("\n");

        return $html === '' ? null : $html;
    }

    /**
     * @return array<int, PublicWidgetPayloadContributor>
     */
    private function contributors(): array
    {
        if ($this->contributors !== null) {
            return $this->contributors;
        }

        $this->contributors = collect(app()->tagged(PublicWidgetPayloadContributor::TAG))
            ->filter(fn (mixed $contributor): bool => $contributor instanceof PublicWidgetPayloadContributor)
            ->sortBy(fn (PublicWidgetPayloadContributor $contributor): int => $contributor->priority())
            ->values()
            ->all();

        return $this->contributors;
    }
}
