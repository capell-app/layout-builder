<?php

declare(strict_types=1);

namespace Capell\Assistant\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class PromptData extends Data
{
    public function __construct(
        public ?string $model = null,
        public bool $titleGeneration = false,
        public ?string $titleGenerationSystem = null,
        public ?string $titleGenerationUserTemplate = null,
        public bool $metaDescription = false,
        public ?string $metaDescriptionSystem = null,
        public ?string $metaDescriptionUserTemplate = null,
        public bool $contentGeneration = false,
        public ?string $contentGenerationSystem = null,
        public ?string $contentGenerationUserTemplate = null,
    ) {}
}
