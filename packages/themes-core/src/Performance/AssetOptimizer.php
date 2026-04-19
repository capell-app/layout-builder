<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Performance;

/**
 * Emits resource hint tags (preload, dns-prefetch, preconnect) for critical
 * assets. Designed to be prepended to the `<head>` before other markup.
 */
class AssetOptimizer
{
    /** @var list<array{rel: string, href: string, as?: string, type?: string, crossorigin?: string}> */
    private array $hints = [];

    public function preload(string $href, string $as, ?string $type = null, ?string $crossorigin = null): self
    {
        $hint = ['rel' => 'preload', 'href' => $href, 'as' => $as];
        if ($type !== null) {
            $hint['type'] = $type;
        }

        if ($crossorigin !== null) {
            $hint['crossorigin'] = $crossorigin;
        }

        $this->hints[] = $hint;

        return $this;
    }

    public function dnsPrefetch(string $href): self
    {
        $this->hints[] = ['rel' => 'dns-prefetch', 'href' => $href];

        return $this;
    }

    public function preconnect(string $href, bool $crossorigin = true): self
    {
        $hint = ['rel' => 'preconnect', 'href' => $href];
        if ($crossorigin) {
            $hint['crossorigin'] = 'anonymous';
        }

        $this->hints[] = $hint;

        return $this;
    }

    /**
     * @return list<array<string, string>>
     */
    public function hints(): array
    {
        return $this->hints;
    }

    public function render(): string
    {
        $lines = [];
        foreach ($this->hints as $hint) {
            $parts = [];
            foreach ($hint as $attr => $value) {
                $parts[] = sprintf('%s="%s"', $attr, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            }

            $lines[] = '<link ' . implode(' ', $parts) . ' />';
        }

        return implode("\n", $lines);
    }
}
