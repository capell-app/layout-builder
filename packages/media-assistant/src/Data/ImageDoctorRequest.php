<?php

declare(strict_types=1);

namespace Capell\MediaAssistant\Data;

final readonly class ImageDoctorRequest
{
    public function __construct(
        public string $operation,
        public string $instructions,
    ) {}
}
