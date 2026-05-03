<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Addresses\Pages;

use Capell\Address\Enums\ResourceEnum;
use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Admin\Support\AdminSurfaceLookup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageAddresses extends ManageRecords
{
    /** @return class-string<AddressResource> */
    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Address);
    }

    protected function getActions(): array
    {
        $countryResource = AdminSurfaceLookup::resource(ResourceEnum::Country);

        return [
            CreateAction::make(),
            Action::make('countries')
                ->color('gray')
                ->url($countryResource::getUrl())
                ->label($countryResource::getNavigationLabel())
                ->icon($countryResource::getNavigationIcon()),
        ];
    }
}
