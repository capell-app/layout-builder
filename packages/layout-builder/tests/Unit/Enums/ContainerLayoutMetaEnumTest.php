<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\ContainerAlignmentEnum;
use Capell\LayoutBuilder\Enums\ResponsiveVisibilityEnum;

it('provides translated container alignment labels', function (ContainerAlignmentEnum $alignment, string $expectedLabel): void {
    expect($alignment->getLabel())->toBe($expectedLabel);
})->with([
    [ContainerAlignmentEnum::Start, 'Start'],
    [ContainerAlignmentEnum::Center, 'Center'],
    [ContainerAlignmentEnum::End, 'End'],
    [ContainerAlignmentEnum::Stretch, 'Stretch'],
]);

it('provides translated responsive visibility labels', function (ResponsiveVisibilityEnum $visibility, string $expectedLabel): void {
    expect($visibility->getLabel())->toBe($expectedLabel);
})->with([
    [ResponsiveVisibilityEnum::Mobile, 'Mobile'],
    [ResponsiveVisibilityEnum::Tablet, 'Tablet'],
    [ResponsiveVisibilityEnum::Desktop, 'Desktop'],
]);
