<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Support\BlockRegistry;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array<int, array<string, mixed>> run(?string $block = null, ?string $variant = null, ?string $theme = null, ?int $limit = null)
 */
final class BuildBlockVisualRegressionManifestAction
{
    use AsObject;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(?string $block = null, ?string $variant = null, ?string $theme = null, ?int $limit = null): array
    {
        $entries = [];

        foreach (resolve(BlockRegistry::class)->all() as $definition) {
            if ($block !== null && $definition->key !== $block) {
                continue;
            }

            foreach ($definition->variants as $blockVariant) {
                if ($variant !== null && $blockVariant->key->value() !== $variant) {
                    continue;
                }

                foreach ($this->scenarios() as $scenario => $fixture) {
                    foreach (['mobile', 'tablet', 'desktop'] as $viewport) {
                        $entries[] = $this->entry($definition, $blockVariant, $viewport, $theme, $scenario, $fixture);
                    }
                }
            }
        }

        return $limit === null ? $entries : array_slice($entries, 0, max(0, $limit));
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @return array<string, mixed>
     */
    private function entry(BlockDefinitionData $definition, BlockVariantData $variant, string $viewport, ?string $theme, string $scenario, array $fixture): array
    {
        $themeKey = $theme ?? 'default';

        return [
            'block' => $definition->key,
            'variant' => $variant->key->value(),
            'theme' => $themeKey,
            'viewport' => $viewport,
            'scenario' => $scenario,
            'artifact' => implode('/', [
                'blocks',
                $this->artifactSegment($definition->key),
                Str::slug($themeKey),
                Str::slug($variant->key->value()) . '-' . Str::slug($scenario) . '-' . $viewport . '.png',
            ]),
            'fixture' => $this->fixture([
                'title' => 'Fixture heading',
                'content' => 'Fixture content that is stable across runs.',
                'items' => [
                    ['title' => 'First item', 'summary' => 'Stable first item.', 'image' => ['alt' => 'First fixture image']],
                    ['title' => 'Second item', 'summary' => 'Stable second item.', 'image' => ['decorative' => true]],
                ],
                'cta' => ['label' => 'Fixture action', 'url' => '#fixture-action'],
                'media' => ['alt' => 'Fixture media', 'ratio' => '16:9'],
                'presentation' => [
                    'variant' => $variant->key->value(),
                    'spacing' => 'normal',
                    'background' => 'default',
                    'mediaPosition' => 'top',
                    'cardsPerRow' => 3,
                    'showCta' => true,
                    'headingWidth' => 'normal',
                    'anchorId' => null,
                ],
            ], $fixture),
        ];
    }

    /**
     * @param  array<string, mixed>  $baseFixture
     * @param  array<string, mixed>  $scenarioFixture
     * @return array<string, mixed>
     */
    private function fixture(array $baseFixture, array $scenarioFixture): array
    {
        foreach (['media', 'cta'] as $replaceKey) {
            if (array_key_exists($replaceKey, $scenarioFixture)) {
                $baseFixture[$replaceKey] = $scenarioFixture[$replaceKey];
                unset($scenarioFixture[$replaceKey]);
            }
        }

        return array_replace_recursive($baseFixture, $scenarioFixture);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function scenarios(): array
    {
        return [
            'default' => [],
            'long-content' => [
                'title' => 'A deliberately long fixture heading that should wrap cleanly on narrow screens',
                'content' => 'Long stable fixture copy validates rhythm, line-height, and responsive wrapping without relying on tenant data.',
            ],
            'max-cards' => [
                'items' => [
                    ['title' => 'One', 'summary' => 'Stable card.', 'image' => ['alt' => 'Card one image']],
                    ['title' => 'Two', 'summary' => 'Stable card.', 'image' => ['alt' => 'Card two image']],
                    ['title' => 'Three', 'summary' => 'Stable card.', 'image' => ['alt' => 'Card three image']],
                    ['title' => 'Four', 'summary' => 'Stable card.', 'image' => ['alt' => 'Card four image']],
                    ['title' => 'Five', 'summary' => 'Stable card.', 'image' => ['alt' => 'Card five image']],
                    ['title' => 'Six', 'summary' => 'Stable card.', 'image' => ['alt' => 'Card six image']],
                ],
                'presentation' => ['cardsPerRow' => 3],
            ],
            'dark-surface' => [
                'presentation' => ['background' => 'dark'],
            ],
            'image-surface-missing-alt' => [
                'media' => ['ratio' => '4:3'],
                'presentation' => ['background' => 'image'],
            ],
            'cta-hidden' => [
                'cta' => [],
                'presentation' => ['showCta' => false],
            ],
        ];
    }

    private function artifactSegment(string $value): string
    {
        return Str::slug(str_replace(['.', '_'], '-', $value));
    }
}
