<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Components\Forms;

use Capell\Address\Enums\ModelEnum;
use Capell\Address\Filament\Resources\Countries\Schemas\CountryForm;
use Capell\Address\Models\Country;
use Capell\Core\Facades\CapellCore;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Override;

class CountrySelect extends Select
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-address::form.country'))
            ->searchable()
            ->options(
                fn (self $component): array => CapellCore::getModel(ModelEnum::Country)::query()
                    ->limit($component->getOptionsLimit())
                    ->ordered()
                    ->get()
                    ->mapWithKeys(fn (Country $country): array => [$country->getKey() => $country->name])
                    ->all(),
            )
            ->getOptionLabelUsing(
                fn (?string $value): ?string => CapellCore::getModel(ModelEnum::Country)::query()
                    ->find($value)
                    ?->name,
            )
            ->getSearchResultsUsing(
                fn (self $component, string $search): array => CapellCore::getModel(ModelEnum::Country)::query()
                    ->where(
                        fn (Builder $query): Builder => $query->where('name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('iso2', 'like', $search)
                            ->orWhere('iso3', 'like', $search),
                    )
                    ->limit($component->getOptionsLimit())
                    ->ordered()
                    ->get()
                    ->mapWithKeys(fn (Country $country): array => [$country->getKey() => $country->name])
                    ->all(),
            );
    }

    public function withCreateForm(): self
    {
        return $this->createOptionForm(fn (Schema $schema): Schema => CountryForm::configure($schema)
            ->model(CapellCore::getModel(ModelEnum::Country)))
            ->createOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-admin::generic.language'))
                    ->model(CapellCore::getModel(ModelEnum::Country))
                    ->successNotificationTitle(
                        fn (Action $action): string => __(
                            'capell-admin::notification.created_successfully',
                            ['name' => $action->getModalHeading()],
                        ),
                    )
                    ->after(function (Action $action): void {
                        $action->success();
                    }),
            );
    }

    public function withEditForm(): self
    {
        return $this->fillEditOptionActionFormUsing(static function (Select $component): array {
            $record = $component->getSelectedRecord();

            return $record?->attributesToArray() ?? [];
        })
            ->editOptionForm(fn (Schema $schema): Schema => CountryForm::configure($schema))
            ->editOptionAction(
                fn (Action $action): Action => $action
                    ->modalHeading(__('capell-address::form.country'))
                    ->model(CapellCore::getModel(ModelEnum::Country))
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
