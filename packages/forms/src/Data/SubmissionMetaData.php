<?php

declare(strict_types=1);

namespace Capell\Forms\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class SubmissionMetaData extends Data
{
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $url = null,
        public ?string $referer = null,
    ) {}
}
