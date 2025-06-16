<?php

declare(strict_types=1);

use Capell\Core\Enums\TypeEnum as CoreTypeEnum;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentTypeFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models;

it('has many contents', function (): void {
    $type = (new ContentTypeFactory())->create();

    Models\Content::factory()->create(['type_id' => $type->id]);

    expect($type->refresh())
        ->contents->toHaveCount(1);
});

it('has many widgets', function (): void {
    $type = Type::factory()->widget()->create();

    Models\Widget::factory()->create(['type_id' => $type->id]);

    expect($type->refresh())
        ->widgets->toHaveCount(1);
});

it('can scope content type', function (): void {
    Type::factory()->create(['type' => LayoutTypeEnum::Content]);
    Type::factory()->create(['type' => CoreTypeEnum::Page]);

    $result = Type::contentType()->get();

    expect($result)->toHaveCount(1);
});

it('can scope widget type', function (): void {
    Type::factory()->create(['type' => LayoutTypeEnum::Widget]);
    Type::factory()->create(['type' => LayoutTypeEnum::Content]);

    $result = Type::widgetType()->get();

    expect($result)->toHaveCount(1);
});
