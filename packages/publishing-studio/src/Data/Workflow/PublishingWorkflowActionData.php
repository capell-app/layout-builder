<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Workflow;

use Spatie\LaravelData\Data;

final class PublishingWorkflowActionData extends Data
{
    public function __construct(
        public readonly string $label,
        public readonly int $count,
        public readonly string $severity,
        public readonly string $owner,
        public readonly string $nextActionLabel,
        public readonly string $url,
        public readonly string $sourcePackage = 'capell-app/publishing-studio',
        public readonly ?string $permission = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'label' => $this->label,
            'count' => $this->count,
            'severity' => $this->severity,
            'owner' => $this->owner,
            'nextActionLabel' => $this->nextActionLabel,
            'url' => $this->url,
            'sourcePackage' => $this->sourcePackage,
            'permission' => $this->permission,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
