<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Enums\ModelEnum;
use Capell\Address\Filament\Resources\Addresses\Schemas\AddressForm;
use Capell\Address\Models\Address;
use Capell\Core\Facades\CapellCore;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
                fn (self $component): array => CapellCore::getModel(ModelEnum::Address)::query()
                    ->limit($component->getOptionsLimit())
                    ->ordered()
                    ->get()
                    ->mapWithKeys(fn (Address $address): array => [$address->getKey() => $address->name])
                    ->all()
            )
            ->getSelectedRecordUsing(
                fn (int $state): Address => CapellCore::getModel(ModelEnum::Address)::query()
                    ->find($state)
            )
            ->getOptionLabelUsing(
                fn (?string $value): ?string => CapellCore::getModel(ModelEnum::Address)::query()
                    ->whereKey($value)
                    ->value('name')
            )
            ->getSearchResultsUsing(
                fn (self $component, string $search): array => CapellCore::getModel(ModelEnum::Address)::query()
                    ->where(fn (Builder $query): Builder => $query->where('address_line_1', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('address_line_2', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('city', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('state', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('postal_code', 'like', sprintf('%%%s%%', $search))
                        ->orWhereRelation('country', 'name', 'like', sprintf('%%%s%%', $search)))
                    ->limit($component->getOptionsLimit())
                    ->ordered()
                    ->pluck('name', 'id')
                    ->all()
            );
    }

    public function withCreateForm(): self
    {
        return $this->createOptionForm(fn (Schema $schema): Schema => AddressForm::configure($schema))
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::generic.address'))
                    ->model(CapellCore::getModel(ModelEnum::Address))
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()]
                        )
                    )
            );
    }

    public function withEditForm(): self
    {
        return $this->fillEditOptionActionFormUsing(static function (Select $component): array {
            $record = $component->getSelectedRecord();

            return $record?->attributesToArray() ?? [];
        })
            ->editOptionForm(fn (Schema $schema): Schema => AddressForm::configure($schema))
            ->updateOptionUsing(static function (array $data, Schema $schema): void {
                $schema->getRecord()->update($data);
            })
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::generic.address'))
                    ->modalWidth(Width::ScreenMedium)
                    ->model(CapellCore::getModel(ModelEnum::Address))
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.updated_successfully',
                            ['name' => $action->getModalHeading()]
                        )
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    })
            );
    }
}
