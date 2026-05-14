<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Data\LayoutFragmentData;

final class LayoutClipboard
{
    private const SESSION_KEY = 'capell.layout-builder.clipboard';

    public function __construct(private ?LayoutFragmentData $fragment = null) {}

    public function copy(LayoutFragmentData $fragment): void
    {
        $this->fragment = $fragment;

        session()->put(self::SESSION_KEY, $fragment);
    }

    public function current(): ?LayoutFragmentData
    {
        $fragment = $this->fragment ?? session()->get(self::SESSION_KEY);

        return $fragment instanceof LayoutFragmentData ? $fragment : null;
    }

    public function hasFragment(): bool
    {
        return $this->current() instanceof LayoutFragmentData;
    }
}
