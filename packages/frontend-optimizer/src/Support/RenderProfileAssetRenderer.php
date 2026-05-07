<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Support;

use Capell\FrontendOptimizer\Enums\AssetKind;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Models\FrontendRenderProfile;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\HtmlString;

class RenderProfileAssetRenderer
{
    public function __construct(private readonly Factory $filesystems) {}

    public function render(string $profileHash): HtmlString
    {
        $profile = FrontendRenderProfile::query()->where('hash', $profileHash)->first();

        if (! $profile instanceof FrontendRenderProfile) {
            return new HtmlString('');
        }

        $html = [];
        $hasInlineCriticalCss = false;

        if (is_string($profile->critical_css_path) && $this->filesystems->disk('local')->exists($profile->critical_css_path)) {
            $hasInlineCriticalCss = true;
            $html[] = '<style>' . $this->escapeStyleContents($this->filesystems->disk('local')->get($profile->critical_css_path)) . '</style>';
        }

        foreach ($this->assetsFromProfile($profile) as $asset) {
            $html[] = $this->renderAsset($asset, $hasInlineCriticalCss);
        }

        return new HtmlString(implode(PHP_EOL, array_filter($html, static fn (string $tag): bool => $tag !== '')));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assetsFromProfile(FrontendRenderProfile $profile): array
    {
        $assets = $profile->signature['assets'] ?? [];

        if (! is_array($assets)) {
            return [];
        }

        return array_values(array_filter($assets, static fn (mixed $asset): bool => is_array($asset)));
    }

    /** @param array<string, mixed> $asset */
    private function renderAsset(array $asset, bool $hasInlineCriticalCss): string
    {
        $kind = AssetKind::tryFrom((string) ($asset['kind'] ?? ''));

        return match ($kind) {
            AssetKind::Css => $this->renderCss($asset, $hasInlineCriticalCss),
            AssetKind::Js => $this->renderJs($asset),
            default => '',
        };
    }

    /** @param array<string, mixed> $asset */
    private function renderCss(array $asset, bool $hasInlineCriticalCss): string
    {
        $href = $this->escape((string) ($asset['path'] ?? ''));
        $strategy = AssetLoadingStrategy::tryFrom((string) ($asset['loading_strategy'] ?? '')) ?? AssetLoadingStrategy::Deferred;

        if ($href === '') {
            return '';
        }

        return match ($strategy) {
            AssetLoadingStrategy::Critical => $hasInlineCriticalCss ? '' : sprintf('<link rel="stylesheet" href="%s">', $href),
            AssetLoadingStrategy::Blocking => sprintf('<link rel="stylesheet" href="%s">', $href),
            AssetLoadingStrategy::Preload => sprintf('<link rel="preload" as="style" href="%s" onload="this.onload=null;this.rel=\'stylesheet\'"><noscript><link rel="stylesheet" href="%s"></noscript>', $href, $href),
            AssetLoadingStrategy::Deferred,
            AssetLoadingStrategy::Lazy,
            AssetLoadingStrategy::Interaction,
            AssetLoadingStrategy::Idle => sprintf('<link rel="stylesheet" href="%s" media="print" onload="this.media=\'all\'"><noscript><link rel="stylesheet" href="%s"></noscript>', $href, $href),
        };
    }

    /** @param array<string, mixed> $asset */
    private function renderJs(array $asset): string
    {
        $src = $this->escape((string) ($asset['path'] ?? ''));
        $strategy = AssetLoadingStrategy::tryFrom((string) ($asset['loading_strategy'] ?? '')) ?? AssetLoadingStrategy::Deferred;

        if ($src === '') {
            return '';
        }

        return match ($strategy) {
            AssetLoadingStrategy::Blocking => sprintf('<script src="%s"></script>', $src),
            AssetLoadingStrategy::Interaction,
            AssetLoadingStrategy::Idle,
            AssetLoadingStrategy::Lazy,
            AssetLoadingStrategy::Critical,
            AssetLoadingStrategy::Preload,
            AssetLoadingStrategy::Deferred => sprintf('<script type="module" defer src="%s"></script>', $src),
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escapeStyleContents(string $value): string
    {
        return str_ireplace('</style', '<\\/style', $value);
    }
}
