<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadContributor;
use Capell\LayoutBuilder\Contracts\PublicBlockPayloadResolver;
use Capell\LayoutBuilder\Models\Widget;

class DefaultPublicBlockPayloadResolver implements PublicBlockPayloadResolver
{
    /**
     * @var array<int, object>|null
     */
    private ?array $contributors = null;

    /**
     * @return array<string, mixed>
     */
    public function data(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): array
    {
        $data = [
            'title' => $block->translation?->title,
            'content' => $block->translation?->content,
        ];

        foreach ($this->contributors() as $contributor) {
            $data = array_replace_recursive(
                $data,
                $contributor->data($block, $page, $language, $containerKey, $occurrence),
            );
        }

        return $data;
    }

    public function html(Widget $block, Page $page, Language $language, string $containerKey, int $occurrence): ?string
    {
        $html = collect($this->contributors())
            ->map(fn (object $contributor): ?string => $contributor->html($block, $page, $language, $containerKey, $occurrence))
            ->filter(fn (?string $html): bool => is_string($html) && trim($html) !== '')
            ->implode("\n");

        return $html === '' ? null : $html;
    }

    /**
     * @return array<int, object>
     */
    private function contributors(): array
    {
        if ($this->contributors !== null) {
            return $this->contributors;
        }

        $this->contributors = collect(app()->tagged(PublicBlockPayloadContributor::TAG))
            ->filter(fn (mixed $contributor): bool => is_object($contributor)
                && method_exists($contributor, 'priority')
                && method_exists($contributor, 'data')
                && method_exists($contributor, 'html'))
            ->sortBy(fn (object $contributor): int => $contributor->priority())
            ->values()
            ->all();

        return $this->contributors;
    }
}
