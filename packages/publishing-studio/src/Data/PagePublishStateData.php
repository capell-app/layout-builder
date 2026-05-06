<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data;

use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Aggregates workspace + draft + preview state for a single Page record.
 * Consumed by the PublishStatusPanel Livewire component.
 */
class PagePublishStateData extends Data
{
    public function __construct(
        public int $pageId,
        public bool $isDraft,
        public ?CarbonImmutable $publishedAt,
        public ?string $previewUrl,
        public ?int $workspaceId,
        public ?string $workspaceName,
        public ?WorkspaceStatusEnum $workspaceStatus,
    ) {}

    public function hasActiveWorkspace(): bool
    {
        return $this->workspaceId !== null;
    }

    public function isPublished(): bool
    {
        return $this->publishedAt instanceof CarbonImmutable && ! $this->isDraft;
    }

    public function statusLabel(): string
    {
        if ($this->isDraft && $this->hasActiveWorkspace()) {
            return __('capell-admin::publish_panel.status_draft_in_workspace', ['workspace' => $this->workspaceName]);
        }

        if ($this->isDraft) {
            return __('capell-admin::publish_panel.status_draft');
        }

        if ($this->isPublished()) {
            return __('capell-admin::publish_panel.status_published');
        }

        return __('capell-admin::publish_panel.status_unknown');
    }
}
