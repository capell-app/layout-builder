<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\ElementTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;

it('creates layout builder element types with clear names and descriptions', function (): void {
    resolve(TypeCreator::class)->createElementTypes();

    $types = Blueprint::query()
        ->where('type', LayoutTypeEnum::Element->value)
        ->get()
        ->keyBy('key');

    $heroType = $types->get(ElementTypeEnum::Hero->value);
    $callToActionType = $types->get(ElementTypeEnum::CTASection->value);
    $imageGalleryType = $types->get(ElementTypeEnum::ImageGallery->value);
    $systemType = $types->get(ElementTypeEnum::System->value);

    expect($heroType?->name)->toBe('Hero')
        ->and(data_get($heroType?->admin, 'notes'))->toBe('The main opening block for a page headline, intro copy, and primary action.')
        ->and($callToActionType?->name)->toBe('Call to action')
        ->and(data_get($callToActionType?->admin, 'notes'))->toBe('A focused prompt that sends visitors to the next useful step.')
        ->and($imageGalleryType?->name)->toBe('Image gallery')
        ->and(data_get($systemType?->admin, 'notes'))->toBe('A protected block for generated layout output such as slots and breadcrumbs.');
});
