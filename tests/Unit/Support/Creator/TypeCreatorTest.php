<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
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
