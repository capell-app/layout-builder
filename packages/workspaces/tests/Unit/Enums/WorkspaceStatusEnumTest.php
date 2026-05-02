<?php

declare(strict_types=1);

use Capell\Workspaces\Enums\WorkspaceStatusEnum;

it('marks only Open as editable', function (): void {
    expect(WorkspaceStatusEnum::Open->isEditable())->toBeTrue()
        ->and(WorkspaceStatusEnum::InReview->isEditable())->toBeFalse()
        ->and(WorkspaceStatusEnum::Approved->isEditable())->toBeFalse()
        ->and(WorkspaceStatusEnum::Publishing->isEditable())->toBeFalse()
        ->and(WorkspaceStatusEnum::Published->isEditable())->toBeFalse()
        ->and(WorkspaceStatusEnum::Abandoned->isEditable())->toBeFalse();
});

it('treats Published and Abandoned as terminal', function (): void {
    expect(WorkspaceStatusEnum::Published->isTerminal())->toBeTrue()
        ->and(WorkspaceStatusEnum::Abandoned->isTerminal())->toBeTrue()
        ->and(WorkspaceStatusEnum::Open->isTerminal())->toBeFalse()
        ->and(WorkspaceStatusEnum::InReview->isTerminal())->toBeFalse()
        ->and(WorkspaceStatusEnum::Approved->isTerminal())->toBeFalse()
        ->and(WorkspaceStatusEnum::Publishing->isTerminal())->toBeFalse();
});

it('identifies InReview and Approved as in the approval pipeline', function (): void {
    expect(WorkspaceStatusEnum::InReview->isInApprovalPipeline())->toBeTrue()
        ->and(WorkspaceStatusEnum::Approved->isInApprovalPipeline())->toBeTrue()
        ->and(WorkspaceStatusEnum::Open->isInApprovalPipeline())->toBeFalse()
        ->and(WorkspaceStatusEnum::Publishing->isInApprovalPipeline())->toBeFalse()
        ->and(WorkspaceStatusEnum::Published->isInApprovalPipeline())->toBeFalse()
        ->and(WorkspaceStatusEnum::Abandoned->isInApprovalPipeline())->toBeFalse();
});

it('exposes a color, icon and label for every case', function (): void {
    foreach (WorkspaceStatusEnum::cases() as $status) {
        expect($status->getColor())->toBeString()->not->toBeEmpty()
            ->and($status->getLabel())->toBeString()
            ->and($status->getIcon())->not->toBeNull();
    }
});

it('uses stable string values for persistence', function (): void {
    expect(WorkspaceStatusEnum::Open->value)->toBe('open')
        ->and(WorkspaceStatusEnum::InReview->value)->toBe('in_review')
        ->and(WorkspaceStatusEnum::Approved->value)->toBe('approved')
        ->and(WorkspaceStatusEnum::Publishing->value)->toBe('publishing')
        ->and(WorkspaceStatusEnum::Published->value)->toBe('published')
        ->and(WorkspaceStatusEnum::Abandoned->value)->toBe('abandoned');
});
