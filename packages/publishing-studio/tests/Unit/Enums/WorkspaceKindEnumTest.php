<?php

declare(strict_types=1);

use Capell\PublishingStudio\Enums\WorkspaceKindEnum;

it('exposes a SinglePageDraft case with a stable string value', function (): void {
    expect(WorkspaceKindEnum::SinglePageDraft->value)->toBe('single_page_draft');
});

it('dashboard-dashboard_reports SinglePageDraft as non-automated', function (): void {
    expect(WorkspaceKindEnum::SinglePageDraft->isAutomated())->toBeFalse();
});

it('returns a label for SinglePageDraft', function (): void {
    expect(WorkspaceKindEnum::SinglePageDraft->getLabel())->toBeString()->not->toBe('');
});
