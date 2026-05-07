<?php

declare(strict_types=1);

use Capell\ContentSections\Filament\Resources\Sections\SectionResource;

it('nests reusable sections below pages in content navigation', function (): void {
    expect(SectionResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_content'))
        ->and(SectionResource::getNavigationParentItem())->toBe((string) __('capell-admin::navigation.pages'));
});
