<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadResolver;
use Capell\LayoutBuilder\Models\Widget;

class DefaultPublicLayoutWidgetPayloadResolver implements PublicLayoutWidgetPayloadResolver
{
    /**
     * @var array<int, PublicLayoutWidgetPayloadContributor>|null
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
            ->map(fn (PublicLayoutWidgetPayloadContributor $contributor): ?string => $contributor->html($widget, $page, $language, $containerKey, $occurrence))
            ->filter(fn (?string $html): bool => is_string($html) && trim($html) !== '')
            ->implode("\n");

        return $html === '' ? null : $html;
    }

    /**
     * @return array<int, PublicLayoutWidgetPayloadContributor>
     */
    private function contributors(): array
    {
        if ($this->contributors !== null) {
            return $this->contributors;
        }

        $this->contributors = collect(app()->tagged(PublicLayoutWidgetPayloadContributor::TAG))
            ->filter(fn (mixed $contributor): bool => $contributor instanceof PublicLayoutWidgetPayloadContributor)
            ->sortBy(fn (PublicLayoutWidgetPayloadContributor $contributor): int => $contributor->priority())
            ->values()
            ->all();

        return $this->contributors;
    }
}
