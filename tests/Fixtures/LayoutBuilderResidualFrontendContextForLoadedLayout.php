<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Frontend\Contracts\FrontendContextReader;

final class LayoutBuilderResidualFrontendContextForLoadedLayout implements FrontendContextReader
{
    /** @var array<string, mixed> */
    private array $frontendData = [];

    public function __construct(
        private readonly Layout $layout,
        private readonly Language $language,
        private readonly Page $page,
    ) {}

    public function layout(): Layout
    {
        return $this->layout;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function page(): Page
    {
        return $this->page;
    }

    public function site(): ?Site
    {
        return null;
    }

    public function theme(): ?Theme
    {
        return null;
    }

    /** @return array<string, mixed> */
    public function params(): array
    {
        return [];
    }

    public function slug(): ?string
    {
        return null;
    }

    public function isError(): bool
    {
        return false;
    }

    public function setFrontendData(string $key, mixed $value): self
    {
        $this->frontendData[$key] = $value;

        return $this;
    }

    public function getFrontendData(?string $key = null): mixed
    {
        return $key === null ? $this->frontendData : ($this->frontendData[$key] ?? null);
    }
}
