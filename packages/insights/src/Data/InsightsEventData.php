<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Capell\Insights\Enums\InsightsEventType;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class InsightsEventData extends Data
{
    public function __construct(
        public InsightsEventType $type,
        public string $url,
        public ?string $title = null,
        public ?string $eventName = null,
        public ?string $label = null,
        public ?string $location = null,
        public ?string $targetSelector = null,
        public ?int $viewportX = null,
        public ?int $viewportY = null,
        public ?int $documentX = null,
        public ?int $documentY = null,
        public ?InsightsEventMetadataData $metadata = null,
    ) {}

    public function path(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return '/';
        }

        return $path;
    }
}
