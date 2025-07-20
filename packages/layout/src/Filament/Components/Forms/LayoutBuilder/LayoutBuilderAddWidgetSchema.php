<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\LayoutBuilder;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSelect;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LayoutBuilderAddWidgetSchema
{
    public static function schema(?Collection $containers): array
    {
        return [
            Fieldset::make(__('capell-admin::heading.select_widget'))
                ->columns(1)
                ->inlineLabel()
                ->schema([
                    CheckboxList::make('filter_groups')
                        ->label(__('capell-admin::form.widget_group'))
                        ->dehydrated(false)
                        ->reactive()
                        ->columns(4)
                        ->options(fn (): array => [
                            'default' => __('capell-admin::generic.default'),
                            ...self::getWidgetTypeGroupsOptions(),
                        ])
                        ->afterStateUpdated(function (Set $set): void {
                            $set('widgets', null);
                        }),
                    WidgetSelect::make('widgets')
                        ->required()
                        ->autofocus()
                        ->multiple()
                        ->searchable()
                        ->allowHtml()
                        ->placeholder(__('capell-admin::form.select_widget'))
                        ->withCreateForm()
                        ->options(
                            fn (WidgetSelect $component, Get $get): array => self::getWidgetOptions(
                                $get('type_id'),
                                $get('filter_groups'),
                            )
                                ->mapWithKeys(function ($widget) use ($component): array {
                                    $data = [
                                        'label' => $widget->name,
                                        'description' => ($widget->type->group ? str($widget->type->group)->title()->append(' - ') : ''),
                                    ];

                                    return [$widget->getKey() => $component::getSelectOption($widget, $data)];
                                })
                                ->toArray()
                        ),
                ]),
            ...$containers instanceof Collection ? [
                Select::make('container')
                    ->label(__('capell-admin::form.add_widget_to_container'))
                    ->required()
                    ->inlineLabel()
                    ->options($containers),
            ] : [],
        ];
    }

    private static function getWidgetTypeGroupsOptions(): array
    {
        /** @var class-string<Models\Type> $model */
        $model = CapellCore::getModel(ModelEnum::Type);

        return $model::select('group')
            ->orderByRaw(
                'CASE `group`
                    WHEN "default" THEN 1
                    ELSE 0
                END DESC'
            )
            ->orderByRaw(
                'CASE `group`
                    WHEN "system" THEN 1
                    ELSE 0
                END ASC'
            )
            ->where('type', LayoutTypeEnum::Widget)
            ->whereNotNull('group')
            ->orderBy('group', 'asc')
            ->groupBy('group')
            ->pluck('group')
            ->mapWithKeys(fn ($group): array => [$group => __('capell-admin::generic.'.$group)])
            ->toArray();
    }

    private static function getWidgetOptions(?array $typeId, ?array $group, ?string $search = null): Collection
    {
        return once(
            fn (): Collection => self::getWidgetOptionsQuery($typeId, $group)
                ->when(
                    $search,
                    fn (Builder $query, $search) => $query
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                )
                ->enabled()
                ->ordered()
                ->get()
        );
    }

    private static function getWidgetOptionsQuery(?array $typeId, ?array $groups): Builder
    {
        return CapellCore::getModel(LayoutModelEnum::Widget->name)::query()
            ->with('type')
            ->when($typeId !== null && $typeId !== [], fn (Builder $query) => $query->whereIn('type_id', $typeId))
            ->when(
                $groups,
                fn (Builder $query) => $query->whereHas(
                    'type',
                    fn (BuilderContract $query) => $query->where(fn ($query) => $query
                        ->when(
                            in_array('default', $groups),
                            fn (Builder $query): Builder => $query->whereNull('group')
                        )
                        ->when(
                            count($groups) > 1 || ! in_array('default', $groups, true),
                            function (Builder $query) use ($groups): Builder {
                                if (in_array('default', $groups, true)) {
                                    $groups = array_diff($groups, ['default']);

                                    return $query->orWhereIn('group', $groups);
                                }

                                return $query->whereIn('group', $groups);
                            }
                        )
                    )
                )
            );
    }
}
