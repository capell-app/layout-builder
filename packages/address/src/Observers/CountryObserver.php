<?php

declare(strict_types=1);

namespace Capell\Address\Observers;

use Capell\Address\Models\Country;

class CountryObserver
{
    public function creating(Country $model): void
    {
        if (Country::query()->default()->doesntExist()) {
            $model->default = true;
        }
    }
}
