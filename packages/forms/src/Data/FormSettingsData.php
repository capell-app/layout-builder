<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class FormSettingsData extends Data
{
    public function __construct(
        public ?string $successMessage = null,
        public bool $storeSubmissions = true,
        public ?string $notificationEmail = null,
        public bool $collectIpAddress = true,
        public bool $collectUserAgent = true,
    ) {}
}
