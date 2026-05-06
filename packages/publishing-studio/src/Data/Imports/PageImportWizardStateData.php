<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Imports;

use Spatie\LaravelData\Data;

final class PageImportWizardStateData extends Data
{
    /** @var string */
    public const NOTICE_UNRESOLVED_REFERENCES = 'unresolved_references';

    /** @var string */
    public const NOTICE_BLOCKED_BY_WORKSPACE_CONFLICT = 'blocked_by_workspace_conflict';

    /** @var string */
    public const NOTICE_BLOCKED_PENDING_DECISIONS = 'blocked_pending_decisions';

    /**
     * @param  list<array<string, mixed>>  $reviewRows
     * @param  array<string, array{action: string, notes?: string}>  $pageDecisions
     * @param  list<array<string, mixed>>  $resolveRows
     * @param  array<string, array{action: string, target_id?: int|string|null, notes?: string}>  $relationDecisions
     * @param  array<string, mixed>  $validationSummary
     */
    public function __construct(
        public readonly string $step,
        public readonly ?int $sessionId = null,
        public readonly array $reviewRows = [],
        public readonly array $pageDecisions = [],
        public readonly array $resolveRows = [],
        public readonly array $relationDecisions = [],
        public readonly array $validationSummary = [],
        public readonly string $confirmationExpected = '',
        public readonly ?string $notice = null,
        public readonly ?int $noticeCount = null,
    ) {}
}
