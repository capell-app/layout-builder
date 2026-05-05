<?php

declare(strict_types=1);

namespace Capell\Migrator\Data;

final readonly class ExportOptions
{
    public function __construct(
        public bool $includeTranslations = true,
        public bool $includeMedia = true,
        public bool $includeSharedRelations = true,
        public bool $includeAllContexts = false,
        public ?string $note = null,
        public bool $includeDrafts = false,
        public ?int $sourceWorkspace = null,
    ) {}

    public static function defaults(): self
    {
        return new self;
    }
}
