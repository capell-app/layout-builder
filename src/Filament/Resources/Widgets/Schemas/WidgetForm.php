<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Models\Type;
use Capell\Mosaic\Enums\ConfiguratorTypeEnum;
use Capell\Mosaic\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Schemas\Schema;

class WidgetForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();
        $type = null;

        if ($record?->relationLoaded('type') && $record->type instanceof Type) {
            $type = $record->type;
        }

        $typeId = $configurator->getRawState()['type_id'] ?? $record?->type_id ?? null;

        if (! $type instanceof Type && $typeId !== null) {
            /** @var class-string<Type> $model */
            $model = Type::class;

            $type = $model::query()->find($typeId);
        }

        $adminType = $type instanceof Type
            ? $resolver->resolveForType($type, ConfiguratorTypeEnum::Widget, DefaultWidgetConfigurator::getKey())
            : DefaultWidgetConfigurator::class;

        return $adminType::configure($configurator)->columns();
    }
}
