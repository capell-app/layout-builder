<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Admin\Support\SiteScope;
use Capell\LayoutBuilder\Data\WidgetLayoutUsageData;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Contracts\Auth\Authenticatable;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use UnexpectedValueException;

/**
 * @method static WidgetLayoutUsageData run(Widget $widget)
 */
final class BuildWidgetDeletionImpactAction
{
    use AsFake;
    use AsObject;

    public function handle(Widget $widget): WidgetLayoutUsageData
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable || ! LayoutResource::canViewAny()) {
            return new WidgetLayoutUsageData;
        }

        $projectedUsage = $this->projectedUsage($widget, $actor);

        if ($projectedUsage instanceof WidgetLayoutUsageData) {
            return $projectedUsage;
        }

        /** @var Widget $widget */
        $widget = Widget::query()
            ->withTrashed()
            /** @phpstan-ignore-next-line Widget exposes this local scope through Eloquent. */
            ->withLayoutsCount(LayoutResource::getEloquentQuery())
            ->findOrFail($widget->getKey());

        return $this->projectedUsage($widget, $actor)
            ?? throw new UnexpectedValueException('Expected a widget layout usage count.');
    }

    private function projectedUsage(Widget $widget, Authenticatable $actor): ?WidgetLayoutUsageData
    {
        $layoutsCount = $widget->getAttributes()['layouts_count'] ?? null;

        if (! is_numeric($layoutsCount)) {
            return null;
        }

        return new WidgetLayoutUsageData(
            layouts: (int) $layoutsCount,
            isComplete: true,
            isAuthoritative: SiteScope::isGlobalActor($actor),
        );
    }
}
