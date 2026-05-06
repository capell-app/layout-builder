<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Context;

use Capell\SeoSuite\Contracts\AiActionContextInterface;

final readonly class ContentActionContext implements AiActionContextInterface
{
    public function __construct(
        private string $content,
        private string $keywords = '',
        private null|int|string $pageId = null,
        private ?string $pageType = null,
        private int $languageId = 0,
    ) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function getPageId(): int|string
    {
        return $this->pageId ?? 0;
    }

    public function getPageType(): string
    {
        return $this->pageType ?? '';
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }
}
