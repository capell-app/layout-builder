<?php

declare(strict_types=1);
use Capell\Core\Data\PageTypeData;
use Capell\Core\Models\Type;
use Capell\Hero\Actions\CreateHeroWidgetAction;
use Capell\Layout\Models\Widget;
use Pest\Expectation;

describe('CreateHeroWidgetAction', function (): void {
    it('creates a hero widget with expected attributes', function (): void {
        // Arrange: ensure no widget with key 'hero' exists
        Widget::query()->where('key', 'hero')->delete();

        // Act
        $widget = CreateHeroWidgetAction::run();

        // Assert
        expect($widget)
            ->toBeInstanceOf(Widget::class)
            ->and($widget->key)->toBe('hero')
            ->and($widget->name)->toBe(__('capell-hero::generic.hero'))
            ->and($widget->type_id)->not()->toBeNull()
            ->and($widget->meta['component'])->toBe('capell-hero::widget.hero')
            ->and($widget->admin['icon'])->toBe('heroicon-o-gift');

        // Edge: running again should not create a duplicate
        $widget2 = CreateHeroWidgetAction::run();
        expect($widget2->id)->toBe($widget->id);
    });

    it('creates type with correct attributes', function (): void {
        // Act
        $widget = CreateHeroWidgetAction::run();
        $type = Type::query()->find($widget->type_id);

        // Assert
        expect($type)
            ->not()->toBeNull()
            ->key->toBe('hero')
            ->type->scoped(
                fn (Expectation $typeData) => $typeData->toBeInstanceOf(PageTypeData::class)->name->toBe('widget'),
            )
            ->group->toBe('asset')
            ->admin->icon->toBe('heroicon-o-gift');
    });
});
