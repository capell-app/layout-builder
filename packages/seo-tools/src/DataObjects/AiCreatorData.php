<?php

declare(strict_types=1);

namespace Capell\SeoTools\DataObjects;

final readonly class AiCreatorData
{
    public function __construct(
        public int $siteId,
        public int $userId,
        public string $intent,
        public int $pageCount = 1,
        public ?string $tone = null,
        public ?string $industry = null,
        public ?string $targetAudience = null,
        public ?string $brandVoiceNotes = null,
        public ?int $existingSessionId = null,
    ) {}
}
