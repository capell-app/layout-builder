<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Search;

/**
 * Value object representing a single search hit. Frontends consume these to
 * render result lists regardless of the backing search implementation.
 */
final readonly class SearchResult
{
    public function __construct(
        public string $title,
        public string $url,
        public string $excerpt,
        public string $type = 'page',
        public float $score = 0.0,
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
