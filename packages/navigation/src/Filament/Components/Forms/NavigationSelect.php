<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms;

use Capell\Admin\Filament\Actions\HintEditAction;
use Capell\Admin\Filament\Concerns\HasCustomSelectOption;
use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NavigationSelect extends Select
{
    use HasCustomSelectOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-admin::form.navigation'))
            ->searchable()
            ->allowHtml()
            ->preload()
            ->options(function (Get $get, ?Model $record): array {
                /** @var class-string<Navigation> $model */
                $model = Navigation::class;

                $navigations = $model::query()->when($get('../site_id'), fn (Builder $query, int $siteId): Builder => $query->where(
                    fn (Builder $query): Builder => $query->where('site_id', $siteId)
                        ->orWhereNull('site_id'),
                ))
                    ->when(
                        $record instanceof Pageable,
                        function (Builder $query) use ($record): Builder {
                            if (DB::getDriverName() === 'sqlite') {
                                return $query->whereRaw('EXISTS (SELECT 1 FROM json_each(items) WHERE json_each.value = ?)', [$record->getKey()]);
                            }

                            return $query->whereRaw("JSON_SEARCH(JSON_EXTRACT(items, '$.*'), 'one', ?) IS NOT NULL", [$record->getKey()]);
                        },
                    )
                    ->with(['language', 'site'])
                    ->orderBy('site_id')
                    ->orderBy('name')
                    ->orderBy('language_id')
                    ->get();

                return $navigations->mapWithKeys(
                    function (Navigation $navigation): array {
                        $data = [
                            'label' => $navigation->name,
                            'icon' => $navigation->language !== null ? 'flag-4x3-' . $navigation->language->flag : '',
                            'description' => $navigation->site?->name,
                        ];

                        return [$navigation->getKey() => static::getSelectOption($navigation, $data)];
                    },
                )
                    ->all();
            })
            ->hintAction(
                HintEditAction::make('edit-navigation')
                    ->record(
                        function (null|int|string $state): ?Navigation {
                            /** @var class-string<Navigation> $model */
                            $model = Navigation::class;

                            return $model::query()
                                ->when(
                                    is_numeric($state),
                                    fn (Builder $query): Builder => $query->whereKey($state),
                                    fn (Builder $query): Builder => $query->where('key', $state),
                                )
                                ->first();
                        },
                    )
                    ->url(
                        function (HintEditAction $action): string {
                            /** @var Navigation $record */
                            $record = $action->getRecord();

                            return NavigationResource::getUrl('edit', ['record' => $record->id]);
                        },
                    )
                    ->visible(function (self $component, string $operation, HintEditAction $action): bool {
                        if ($component->isMultiple()) {
                            return false;
                        }

                        /** @var Navigation $record */
                        $record = $action->getRecord();

                        return $record instanceof Navigation;
                    }),
            );
    }
}
