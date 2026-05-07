<?php

declare(strict_types=1);

use Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;

it('moves publishing workflow utilities out of content navigation', function (): void {
    expect(WorkspaceResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_workflow'))
        ->and(ScheduledPublishingPage::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_workflow'))
        ->and(ScheduledPublishingPage::getNavigationItems()[0]->getSort())->toBe(1)
        ->and(StaleDraftsPage::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_workflow'))
        ->and(StaleDraftsPage::getNavigationItems()[0]->getSort())->toBe(2)
        ->and(PreviewLinkResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_workflow'))
        ->and(PreviewLinkResource::getNavigationItems()[0]->getSort())->toBe(3);
});
