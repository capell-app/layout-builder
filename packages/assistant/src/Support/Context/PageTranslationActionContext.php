<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Context;

use Capell\Assistant\Contracts\AiActionContextInterface;
use Capell\Core\Models\PageTranslation;

final readonly class PageTranslationActionContext implements AiActionContextInterface
{
    public function __construct(private PageTranslation $translation) {}

    public function getContent(): string
    {
        return (string) ($this->translation->content ?? '');
    }

    public function getKeywords(): string
    {
        $meta = (array) ($this->translation->meta ?? []);

        return (string) ($meta['keywords'] ?? '');
    }

    public function getPageId(): int
    {
        return (int) $this->translation->page_id;
    }

    public function getPageType(): string
    {
        return '';
    }

    public function getLanguageId(): int
    {
        return (int) ($this->translation->language_id ?? 0);
    }

    public function getTranslation(): PageTranslation
    {
        return $this->translation;
    }
}
