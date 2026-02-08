<?php

declare(strict_types=1);

namespace Capell\Address\Filament\Resources\Countries\Pages;

use Capell\Address\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageCountries extends ManageRecords
{
    /** @return class-string<Capell\Address\Filament\Resources\Countries\CountryResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource(ResourceEnum::Country);
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
