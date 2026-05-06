<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Filament\Resources\Addresses\Schemas\AddressForm;
use Capell\Address\Models\Address;
use Filament\Actions\Action;
use Filament\FormBuilder\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Override;

class AddressSelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-address::form.address'))
            ->searchable()
            ->options(
                function (self $component): array {
                    /** @var class-string<Address> $model */
                    $model = Address::class;

                    return $model::query()
                        ->limit($component->getOptionsLimit())
                        ->ordered()
                        ->get()
                        ->mapWithKeys(fn (Address $address): array => [$address->getKey() => $address->full_address])
                        ->all();
                },
            )
            ->getSelectedRecordUsing(
                function (int $state): Address {
                    /** @var class-string<Address> $model */
                    $model = Address::class;

                    return $model::query()
                        ->find($state);
                },
            )
            ->getOptionLabelUsing(
                function (?string $value): ?string {
                    /** @var class-string<Address> $model */
                    $model = Address::class;

                    return $model::query()
                        ->whereKey($value)
                        ->value('name');
                },
            )
            ->getSearchResultsUsing(
                function (self $component, string $search): array {
                    /** @var class-string<Address> $model */
                    $model = Address::class;

                    return $model::query()
                        ->where(fn (Builder $query): Builder => $query->where('line1', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('line2', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('city', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('state', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('postal_code', 'like', sprintf('%%%s%%', $search))
                            ->orWhereRelation('country', 'name', 'like', sprintf('%%%s%%', $search)))
                        ->limit($component->getOptionsLimit())
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all();
                },
            );
    }

    public function withCreateForm(): self
    {
        return $this->createOptionForm(fn (Schema $configurator): Schema => AddressForm::configure($configurator))
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::generic.address'))
                    ->model(Address::class)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    ),
            );
    }

    public function withEditForm(): self
    {
        return $this->fillEditOptionActionFormUsing(static function (Select $component): array {
            $record = $component->getSelectedRecord();

            return $record?->attributesToArray() ?? [];
        })
            ->editOptionForm(fn (Schema $configurator): Schema => AddressForm::configure($configurator))
            ->updateOptionUsing(static function (array $data, Schema $configurator): void {
                $configurator->getRecord()->update($data);
            })
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::generic.address'))
                    ->modalWidth(Width::ScreenMedium)
                    ->model(Address::class)
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            );
    }
}
