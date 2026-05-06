<?php

declare(strict_types=1);

use Capell\PublishingStudio\Enums\WorkspaceApprovalActionEnum;

it('exposes the canonical approval action values', function (): void {
    expect(WorkspaceApprovalActionEnum::Submitted->value)->toBe('submitted')
        ->and(WorkspaceApprovalActionEnum::Approved->value)->toBe('approved')
        ->and(WorkspaceApprovalActionEnum::Rejected->value)->toBe('rejected')
        ->and(WorkspaceApprovalActionEnum::ChangesRequested->value)->toBe('changes_requested');
});

it('covers the four approval actions without extras', function (): void {
    $names = array_map(
        fn (WorkspaceApprovalActionEnum $case): string => $case->name,
        WorkspaceApprovalActionEnum::cases(),
    );

    expect($names)->toEqualCanonicalizing(['Submitted', 'Approved', 'Rejected', 'ChangesRequested']);
});
