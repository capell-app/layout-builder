<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\Block;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static bool run(Block $block, Pageable $page)
 */
class HeroBlockHasPrimaryHeadingAction
{
    use AsObject;

    private const string FRONTEND_CONTEXT_SERVICE = 'capell.frontend.context';

    public function handle(Block $block, Pageable $page): bool
    {
        $hasPrimaryHeading = false;

        $content = null;

        if ($block->assets->isNotEmpty()) {
            $firstAsset = $block->assets->first()?->asset;
            $firstAssetTranslation = $firstAsset?->getRelationValue('translation');

            if ($firstAssetTranslation instanceof Translation) {
                if ($firstAssetTranslation->title !== null && $firstAssetTranslation->title !== '') {
                    $hasPrimaryHeading = true;
                } elseif ($firstAssetTranslation->content !== null && $firstAssetTranslation->content !== '') {
                    $content = $firstAssetTranslation->content;
                }
            }
        } else {
            $content = $page->translation->meta['hero'] ?? null;
        }

        if (! $hasPrimaryHeading && filled($content)) {
            $hasPrimaryHeading = preg_match('/<h1\b[^>]*>/i', (string) $content) === 1;
        }

        if ($hasPrimaryHeading) {
            $frontend = $this->frontendContext();

            if ($frontend !== null && method_exists($frontend, 'setFrontendData')) {
                $frontend->setFrontendData('has_primary_heading', true);
            }
        }

        return $hasPrimaryHeading;
    }

    private function frontendContext(): ?object
    {
        if (! app()->bound(self::FRONTEND_CONTEXT_SERVICE)) {
            return null;
        }

        $frontend = resolve(self::FRONTEND_CONTEXT_SERVICE);

        return is_object($frontend) ? $frontend : null;
    }
}
