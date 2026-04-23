<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Contracts;

interface AiActionContextInterface
{
    public function getContent(): string;

    public function getKeywords(): string;

    public function getPageId(): int|string;

    public function getPageType(): string;

    public function getLanguageId(): int;
}
