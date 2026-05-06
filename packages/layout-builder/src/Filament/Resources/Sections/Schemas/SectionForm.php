<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Sections\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Filament\Schemas\Schema;

class SectionForm implements FormConfigurator
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
            ? $resolver->resolveForType($type, ConfiguratorTypeEnum::Section, DefaultSectionConfigurator::getKey())
            : DefaultSectionConfigurator::class;

        return $adminType::configure($configurator->columns());
    }
}
