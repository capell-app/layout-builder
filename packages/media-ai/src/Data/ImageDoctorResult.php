<?php

declare(strict_types=1);

namespace Capell\MediaAI\Data;

final readonly class ImageDoctorResult
{
    public function __construct(
        public bool $successful,
        public ?string $message = null,
    ) {}

    public static function success(?string $message = null): self
    {
        return new self(true, $message);
    }

    public static function failure(?string $message = null): self
    {
        return new self(false, $message);
    }
}
