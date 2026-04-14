<?php

declare(strict_types=1);

use Capell\Core\Enums\TypeEnum as CoreTypeEnum;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentTypeFactory;
use Capell\Layout\Database\Factories\WidgetTypeFactory;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;

it('has many contents', function (): void {
    $type = (new ContentTypeFactory)->create();

    Content::factory()->create(['type_id' => $type->id]);

    $type->refresh()->load('contents');

    expect($type->getRelation('contents'))->toHaveCount(1);
});

it('has many widgets', function (): void {
    $type = (new WidgetTypeFactory)->create();

    Widget::factory()->create(['type_id' => $type->id]);

    $type->refresh()->load('widgets');

    expect($type->getRelation('widgets'))->toHaveCount(1);
});

it('can scope content type', function (): void {
    Type::factory()->create(['type' => LayoutTypeEnum::Content]);
    Type::factory()->create(['type' => CoreTypeEnum::Page]);

    $result = Type::query()->where('type', LayoutTypeEnum::Content)->get();

    expect($result)->toHaveCount(1);
});

it('can scope widget type', function (): void {
    Type::factory()->create(['type' => LayoutTypeEnum::Widget]);
    Type::factory()->create(['type' => LayoutTypeEnum::Content]);

    $result = Type::query()->where('type', LayoutTypeEnum::Widget)->get();

    expect($result)->toHaveCount(1);
});
