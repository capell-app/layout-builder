<?php

declare(strict_types=1);

namespace Capell\Search\Enums;

use Filament\Support\Contracts\HasLabel;

enum SearchDriver: string implements HasLabel
{
    case Database = 'database';
    case Scout = 'scout';

    public function getLabel(): string
    {
        return __('capell-search::settings.driver_options.' . $this->value);
    }
}
