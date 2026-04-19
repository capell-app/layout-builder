<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Search;

/**
 * Value object representing a single search hit. Frontends consume these to
 * render result lists regardless of the backing search implementation.
 */
final class SearchResult
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $excerpt,
        public readonly string $type = 'page',
        public readonly float $score = 0.0,
    ) {}

    /**
     * @return array{title: string, url: string, excerpt: string, type: string, score: float}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'url' => $this->url,
            'excerpt' => $this->excerpt,
            'type' => $this->type,
            'score' => $this->score,
        ];
    }
}
