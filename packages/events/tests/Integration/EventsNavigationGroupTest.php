<?php

declare(strict_types=1);

use Capell\Events\Filament\Resources\Events\EventResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Icons\Heroicon;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    test()->actingAsAdmin();
});

it('places events in the content navigation group with the configured group icon', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();

    $contentGroup = collect(Filament::getNavigation())
        ->first(
            fn (NavigationGroup $navigationGroup): bool => $navigationGroup->getLabel() === __('capell-admin::navigation.group_content'),
        );

    expect(EventResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_content'))
        ->and($contentGroup)
        ->toBeInstanceOf(NavigationGroup::class)
        ->and($contentGroup?->getIcon())->toBe(Heroicon::OutlinedDocumentText);
});
