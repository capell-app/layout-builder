<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Support\BlueprintSubjectRegistry;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Enums\WidgetTypeEnum;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;

it('creates layout builder widget types with clear names and descriptions', function (): void {
    resolve(TypeCreator::class)->createWidgetTypes();

    $types = Blueprint::query()
        ->where('type', LayoutTypeEnum::Widget->value)
        ->get()
        ->keyBy('key');

    $heroType = $types->get(WidgetTypeEnum::Hero->value);
    $callToActionType = $types->get(WidgetTypeEnum::CTASection->value);
    $imageGalleryType = $types->get(WidgetTypeEnum::ImageGallery->value);
    $systemType = $types->get(WidgetTypeEnum::System->value);

    expect($heroType?->name)->toBe('Hero')
        ->and(data_get($heroType?->admin, 'notes'))->toBe('The main opening widget for a page headline, intro copy, and primary action.')
        ->and($callToActionType?->name)->toBe('Call to action')
        ->and(data_get($callToActionType?->admin, 'notes'))->toBe('A focused prompt that sends visitors to the next useful step.')
        ->and($imageGalleryType?->name)->toBe('Image gallery')
        ->and(data_get($systemType?->admin, 'notes'))->toBe('A protected widget for generated layout output such as slots and breadcrumbs.');
});

it('creates default and builder section types idempotently for enabled content sections', function (): void {
    $creator = resolve(TypeCreator::class);

    expect(CapellCore::isPackageEnabled('capell-app/content-sections'))->toBeTrue();

    $creator->create('section');
    $creator->createDefaultContentType();
    $creator->createBuilderContentType();

    expect(Blueprint::query()->where('type', 'section')->where('key', 'default')->count())->toBe(1)
        ->and(Blueprint::query()->where('type', 'section')->where('key', 'builder')->count())->toBe(1)
        ->and(resolve(BlueprintSubjectRegistry::class)->descriptor('section')->ownerPackage)->toBe('capell-app/content-sections');
});

it('does not create section types for an uninstalled or disabled content sections package', function (string $state): void {
    match ($state) {
        'uninstalled' => CapellCore::markPackageUninstalled('capell-app/content-sections'),
        'disabled' => CapellCore::markPackageDisabled('capell-app/content-sections'),
        default => throw new InvalidArgumentException('Unknown package state.'),
    };

    expect(CapellCore::isPackageEnabled('capell-app/content-sections'))->toBeFalse();

    $creator = resolve(TypeCreator::class);
    $creator->create('section');
    $creator->createDefaultContentType();
    $creator->createBuilderContentType();

    expect(Blueprint::query()->where('type', 'section')->exists())->toBeFalse();
})->with(['uninstalled', 'disabled']);
