<?php

declare(strict_types=1);

namespace Capell\Address\Support;

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Facades\CapellCore;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class AddressModelRegistrar
{
    /** @var list<class-string> */
    private const MODELS = [
        Address::class,
        Country::class,
    ];

    public static function register(): void
    {
        CapellCore::registerModels(self::MODELS);

        Relation::morphMap(
            collect(self::MODELS)
                ->mapWithKeys(fn (string $modelClass): array => [Str::snake(class_basename($modelClass)) => $modelClass])
                ->all(),
        );
    }
}
