<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Fixtures;

use Capell\Assistant\Contracts\AiActionContextInterface;

class FakeContext implements AiActionContextInterface
{
    public function __construct(
        private readonly string $content = 'Sample',
        private readonly string $keywords = 'kw',
        private readonly int $pageId = 1,
        private readonly int $languageId = 1,
        private readonly string $pageType = 'default',
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getPageType(): string
    {
        return $this->pageType;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }
}
