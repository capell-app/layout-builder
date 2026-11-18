<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Asset meta is editor-authored and reaches section views verbatim, where a value may be echoed
 * into an href or src. A value that would run as a script URL is emptied before render when it sits
 * under a link-like key, or when it is a single token as a URL is. Prose such as
 * "JavaScript: the basics" under an ordinary text key is kept.
 *
 * @method static array<array-key, mixed> run(array<array-key, mixed> $meta)
 */
final class PreparePublicWidgetAssetMetaAction
{
    use AsFake;
    use AsObject;

    private const array EXECUTABLE_SCHEMES = ['javascript', 'vbscript', 'livescript', 'mocha', 'data'];

    private const string LINK_KEY_PATTERN = '/(?:url|href|link|src|uri|action)$/i';

    /** Inline raster images are the only data URLs a section can legitimately show. */
    private const string SAFE_DATA_URL_PATTERN = '~^data:image/(?:png|gif|jpe?g|webp|avif);~';

    /**
     * @param  array<array-key, mixed>  $meta
     * @return array<array-key, mixed>
     */
    public function handle(array $meta): array
    {
        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                $meta[$key] = $this->handle($value);

                continue;
            }

            if (is_string($value) && $this->isExecutableUrl($value) && ($this->isLinkKey($key) || $this->isSingleToken($value))) {
                $meta[$key] = '';
            }
        }

        return $meta;
    }

    private function isLinkKey(int|string $key): bool
    {
        return is_string($key) && preg_match(self::LINK_KEY_PATTERN, $key) === 1;
    }

    private function isSingleToken(string $value): bool
    {
        return preg_match('/\s/u', trim($value)) !== 1;
    }

    private function isExecutableUrl(string $value): bool
    {
        // Browsers ignore entities, control characters and whitespace when reading a URL scheme.
        $normalised = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalised = mb_strtolower((string) preg_replace('~[\x00-\x20\x7F]+~u', '', $normalised));

        if (preg_match('~^([a-z][a-z0-9+.\-]*):~', $normalised, $matches) !== 1) {
            return false;
        }

        if (! in_array($matches[1], self::EXECUTABLE_SCHEMES, true)) {
            return false;
        }

        return preg_match(self::SAFE_DATA_URL_PATTERN, $normalised) !== 1;
    }
}
