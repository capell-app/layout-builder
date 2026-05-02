<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Models\Type;
use Capell\Navigation\Enums\NavigationConfiguratorTypeEnum;
use Capell\Navigation\Filament\Configurators\Navigations\DefaultNavigationConfigurator;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class NavigationForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();

        if ($record instanceof Model && $record->exists) {
            $typeId = $record->getAttribute('type_id');

            /** @var class-string<Type> $model */
            $model = Type::class;

            $type = $typeId !== null ? $model::query()->find($typeId) : null;
            $adminType = $type instanceof Type
                ? $resolver->resolveForType($type, NavigationConfiguratorTypeEnum::Navigation, DefaultNavigationConfigurator::getKey())
                : DefaultNavigationConfigurator::class;

            return $adminType::configure($configurator, $context);
        }

        $typeId = $configurator->getRawState()['type_id'] ?? null;

        /** @var class-string<Type> $model */
        $model = Type::class;

        $type = $typeId !== null ? $model::query()->find($typeId) : null;
        $adminType = $type instanceof Type
            ? $resolver->resolveForType($type, NavigationConfiguratorTypeEnum::Navigation, DefaultNavigationConfigurator::getKey())
            : DefaultNavigationConfigurator::class;

        return $adminType::configure($configurator, $context);
    }
}
