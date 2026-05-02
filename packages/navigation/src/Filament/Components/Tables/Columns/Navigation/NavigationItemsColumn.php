<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Tables\Columns\Navigation;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Navigation\Actions\ResolveNavigationItemModelsAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class NavigationItemsColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::table.items'))
            ->getStateUsing(
                function (Navigation $record): array {
                    $pageLabelsByLookupKey = ResolveNavigationItemModelsAction::run($record->items ?? [])
                        ->mapWithKeys(
                            fn (Model $model): array => [
                                $this->buildLookupKey($model->getMorphClass(), (int) $model->getKey()) => $this->resolveModelLabel($model),
                            ],
                        )
                        ->all();

                    return collect($record->items ?? [])
                        ->map(
                            fn (array $item): string => $item['label']
                                ?? match ($item['type']) {
                                    NavigationItemType::Page->value => $pageLabelsByLookupKey[$this->buildLookupKey(
                                        (string) ($item['data']['pageable_type'] ?? ''),
                                        (int) ($item['data']['pageable_id'] ?? 0),
                                    )] ?? null,
                                    default => null,
                                }
                            ?? __('capell-admin::generic.unknown'),
                        )
                        ->filter()
                        ->values()
                        ->all();
                },
            )
            ->color(FilamentColorEnum::LightGray->value)
            ->wrap()
            ->expandableLimitedList()
            ->toggleable();
    }

    private function resolveModelLabel(Model $model): ?string
    {
        $label = $model->getAttribute('name');

        return is_string($label) && $label !== ''
            ? $label
            : null;
    }

    private function buildLookupKey(string $pageableType, int $pageableId): string
    {
        return $pageableType . ':' . $pageableId;
    }
}
