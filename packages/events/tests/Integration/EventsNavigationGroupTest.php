<?php

declare(strict_types=1);

use Capell\Admin\Enums\NavigationGroupPositionEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Events\Filament\Pages\EventCalendarPage;
use Capell\Events\Filament\Resources\Events\EventResource;
use Capell\Events\Filament\Resources\Occurrences\EventOccurrenceResource;
use Capell\Events\Filament\Resources\Registrations\EventRegistrationResource;
use Capell\Events\Filament\Resources\Venues\EventVenueResource;
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

it('keeps the approved top-level navigation groups in order on the booted admin panel', function (): void {
    CapellAdmin::registerNavigationGroup(
        label: 'capell-admin::navigation.group_marketing',
        icon: Heroicon::OutlinedMegaphone,
        position: NavigationGroupPositionEnum::After,
        relativeTo: 'capell-admin::navigation.group_content',
    );

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();

    $approvedGroups = [
        __('capell-admin::navigation.group_dashboard'),
        __('capell-admin::navigation.group_content'),
        __('capell-admin::navigation.group_marketing'),
        __('capell-admin::navigation.group_workflow'),
        __('capell-admin::navigation.group_monitoring'),
        __('capell-admin::navigation.group_website'),
        __('capell-admin::navigation.group_administration'),
    ];

    $labels = collect(Filament::getCurrentPanel()->getNavigationGroups())
        ->filter(fn (mixed $navigationGroup): bool => $navigationGroup instanceof NavigationGroup)
        ->map(fn (NavigationGroup $navigationGroup): ?string => $navigationGroup->getLabel())
        ->filter()
        ->values()
        ->all();

    expect(array_values(array_intersect($labels, $approvedGroups)))->toBe($approvedGroups);
});

it('keeps events primary and nests supporting event navigation below it', function (): void {
    $eventLabel = (string) __('capell-events::generic.events');

    expect(EventResource::getNavigationItems()[0]->getSort())->toBe(3)
        ->and(EventResource::getNavigationParentItem())->toBeNull()
        ->and(EventCalendarPage::getNavigationParentItem())->toBe($eventLabel)
        ->and(EventOccurrenceResource::getNavigationParentItem())->toBe($eventLabel)
        ->and(EventVenueResource::getNavigationParentItem())->toBe($eventLabel)
        ->and(EventRegistrationResource::getNavigationParentItem())->toBe($eventLabel);
});
