<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\LayoutBuilder;

use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\ModelEnum;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSelect;
use Capell\Layout\Models\Widget;
use Capell\Layout\Helpers\CapellLayoutHelper;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LayoutBuilderAddWidgetSchema
{
    public static function schema(?Collection $containers): array
    {
        return [
            CheckboxList::make('filter_groups')
                ->label(__('capell-admin::generic.filter_widgets'))
                ->dehydrated(false)
                ->lazy()
                ->columns(4)
                ->options(fn (): array => self::getWidgetTypeGroups())
                ->bulkToggleable(),
            WidgetSelect::make('widgets')
                ->placeholder(__('capell-admin::form.select_widget'))
                ->required()
                ->autofocus()
                ->multiple()
                ->searchable()
                ->allowHtml()
                ->withCreateForm()
                ->options(
                    fn (WidgetSelect $component, Get $get): array => CapellLayoutHelper::getWidgetOptions(
                        $get('type_id'),
                        $get('filter_groups'),
                    )
                        ->mapWithKeys(function ($widget) use ($component): array {
                            $data = [
                                'label' => $widget->name,
                                'description' => ($widget->type->group ? str($widget->type->group)->title() : ''),
                            ];

                            return [$widget->getKey() => $component::getSelectOption($widget, $data)];
                        })
                        ->all(),
                ),
            ...$containers instanceof Collection ? [
                Select::make('container')
                    ->label(__('capell-admin::form.container'))
                    ->required()
                    ->options($containers),
            ] : [],
        ];
    }

    private static function getWidgetTypeGroups(): array
    {
        /** @var class-string<Widget> $model */
        $model = CapellCore::getModel(ModelEnum::Widget->name);

        return $model::getTypeGroups()
            ->mapWithKeys(fn ($group): array => [$group => __('capell-admin::generic.' . $group)])
            ->all();
    }
}
