<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Support\Context;

use Capell\Core\Models\Translation;
use Capell\SeoTools\Assistant\Contracts\AiActionContextInterface;

final readonly class TranslationActionContext implements AiActionContextInterface
{
    public function __construct(private Translation $translation) {}

    public function getContent(): string
    {
        return (string) ($this->translation->content ?? '');
    }

    public function getKeywords(): string
    {
        $meta = ($this->translation->meta ?? []);

        return (string) ($meta['keywords'] ?? '');
    }

    public function getPageId(): int
    {
        return $this->translation->translatable_id;
    }

    public function getPageType(): string
    {
        return $this->translation->translatable_type;
    }

    public function getLanguageId(): int
    {
        return $this->translation->language_id ?? 0;
    }

    public function getTranslation(): Translation
    {
        return $this->translation;
    }
}
