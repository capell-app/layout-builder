<?php

declare(strict_types=1);

namespace Capell\Workspaces\Approvals;

use Spatie\LaravelData\Data;

/**
 * One required-reviewer rule produced by {@see ReviewPolicyResolver}.
 *
 * `requiredFor` is a free-form key mirrored to
 * {@see WorkspaceReviewAssignment::$required_for}; callers
 * fill `reviewerType/reviewerId` when they materialise an assignment.
 */
class RequiredReviewer extends Data
{
    public function __construct(
        public string $requiredFor,
        public ?string $role = null,
        public ?string $reviewerType = null,
        public ?int $reviewerId = null,
    ) {}
}
