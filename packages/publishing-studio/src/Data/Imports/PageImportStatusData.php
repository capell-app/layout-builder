<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Data\Imports;

use Spatie\LaravelData\Data;

final class PageImportStatusData extends Data
{
    /** @var string */
    public const NOTICE_SUMMARY_BLOCKING_ERRORS = 'summary_blocking_errors';

    /** @var string */
    public const NOTICE_CONFIRMATION_MISMATCH = 'confirmation_mismatch';

    /** @var string */
    public const NOTICE_IMPORT_QUEUED = 'import_queued';

    /**
     * @param  array<string, mixed>  $resultSummary
     */
    public function __construct(
        public readonly string $step,
        public readonly ?string $sessionStatus = null,
        public readonly array $resultSummary = [],
        public readonly ?string $failureReason = null,
        public readonly ?int $targetWorkspaceId = null,
        public readonly ?string $notice = null,
        public readonly ?string $noticeBody = null,
    ) {}
}
