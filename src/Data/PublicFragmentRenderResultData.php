<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\LayoutBuilder\Enums\PublicFragmentRenderOutcome;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

/**
 * Result of a public widget fragment render.
 *
 * `html` is only ever populated for `PublicFragmentRenderOutcome::Rendered`. The
 * outcome is a server-side diagnostic and must not be echoed to public clients.
 */
final class PublicFragmentRenderResultData extends Data
{
    public function __construct(
        public readonly PublicFragmentRenderOutcome $outcome,
        public readonly ?string $html = null,
    ) {
        if ($outcome->isRendered() && (! is_string($html) || trim($html) === '')) {
            throw new InvalidArgumentException('A rendered public fragment requires non-blank HTML.');
        }

        if (! $outcome->isRendered() && $html !== null) {
            throw new InvalidArgumentException('A failed public fragment cannot contain HTML.');
        }
    }

    public static function rendered(string $html): self
    {
        return new self(PublicFragmentRenderOutcome::Rendered, $html);
    }

    public static function failed(PublicFragmentRenderOutcome $outcome): self
    {
        if ($outcome->isRendered()) {
            throw new InvalidArgumentException('The rendered outcome cannot be used for a failed public fragment.');
        }

        return new self($outcome);
    }
}
