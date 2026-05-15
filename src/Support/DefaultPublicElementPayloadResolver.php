<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicElementPayloadResolver;
use Capell\LayoutBuilder\Models\Element;

class DefaultPublicElementPayloadResolver implements PublicElementPayloadResolver
{
    /**
     * @return array<string, mixed>
     */
    public function data(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): array
    {
        $data = [
            'title' => $element->translation?->title,
            'content' => $element->translation?->content,
        ];

        foreach ($this->contributors() as $contributor) {
            $data = array_replace_recursive(
                $data,
                $contributor->data($element, $page, $language, $containerKey, $occurrence),
            );
        }

        return $data;
    }

    public function html(Element $element, Page $page, Language $language, string $containerKey, int $occurrence): ?string
    {
        $html = collect($this->contributors())
            ->map(fn (object $contributor): ?string => $contributor->html($element, $page, $language, $containerKey, $occurrence))
            ->filter(fn (?string $html): bool => is_string($html) && trim($html) !== '')
            ->implode("\n");

        return $html === '' ? null : $html;
    }

    /**
     * @return array<int, object>
     */
    private function contributors(): array
    {
        return collect(app()->tagged(PublicElementPayloadContributor::TAG))
            ->filter(fn (mixed $contributor): bool => is_object($contributor)
                && method_exists($contributor, 'priority')
                && method_exists($contributor, 'data')
                && method_exists($contributor, 'html'))
            ->sortBy(fn (object $contributor): int => $contributor->priority())
            ->values()
            ->all();
    }
}
