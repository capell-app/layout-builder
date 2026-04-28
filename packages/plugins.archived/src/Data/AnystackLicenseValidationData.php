<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Capell\Plugins\Enums\LicenseStatus;
use DateTimeImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AnystackLicenseValidationData extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $valid,
        public readonly LicenseStatus $status,
        public readonly ?string $licenseId = null,
        public readonly ?string $activationId = null,
        public readonly ?DateTimeImmutable $expiresAt = null,
        public readonly ?string $product = null,
        public readonly ?string $statusCode = null,
        public readonly array $raw = [],
    ) {}
}
