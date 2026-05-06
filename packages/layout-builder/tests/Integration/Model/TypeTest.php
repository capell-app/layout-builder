<?php

declare(strict_types=1);

use Capell\Core\Enums\TypeEnum as CoreTypeEnum;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Database\Factories\ContentTypeFactory;
use Capell\LayoutBuilder\Database\Factories\WidgetTypeFactory;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Models\Section;
use Capell\LayoutBuilder\Models\Widget;

it('has many sections', function (): void {
    $type = (new ContentTypeFactory)->create();

    Section::factory()->create(['type_id' => $type->id]);

    $type->refresh()->load('sections');

    expect($type->getRelation('sections'))->toHaveCount(1);
});

it('has many widgets', function (): void {
    $type = (new WidgetTypeFactory)->create();

    Widget::factory()->create(['type_id' => $type->id]);

    $type->refresh()->load('widgets');

    expect($type->getRelation('widgets'))->toHaveCount(1);
});

it('can scope section type', function (): void {
    Type::factory()->create(['type' => LayoutTypeEnum::Section]);
    Type::factory()->create(['type' => CoreTypeEnum::Page]);

    $result = Type::query()->where('type', LayoutTypeEnum::Section)->get();

    expect($result)->toHaveCount(1);
});

it('can scope widget type', function (): void {
    Type::factory()->create(['type' => LayoutTypeEnum::Widget]);
    Type::factory()->create(['type' => LayoutTypeEnum::Section]);

    $result = Type::query()->where('type', LayoutTypeEnum::Widget)->get();

    expect($result)->toHaveCount(1);
});
