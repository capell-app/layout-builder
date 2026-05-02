<?php

declare(strict_types=1);

namespace Capell\Address\Observers;

use Capell\Address\Models\Address;

class AddressObserver
{
    public function creating(Address $model): void
    {
        if (Address::query()->default()->doesntExist()) {
            $model->default = true;
        }
    }
}
