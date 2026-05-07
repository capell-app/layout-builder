<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Resources\Sections\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\ContentSections\Actions\ResolveRequestedSectionTypeAction;
use Capell\ContentSections\Enums\ConfiguratorTypeEnum;
use Capell\ContentSections\Filament\Configurators\Sections\DefaultSectionConfigurator;
use Capell\Core\Models\Type;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class SectionForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();
        $type = null;

        if ($record instanceof Model && $record->relationLoaded('type')) {
            $loadedType = $record->getRelationValue('type');
            $type = $loadedType instanceof Type ? $loadedType : null;
        }

        $typeId = $configurator->getRawState()['type_id'] ?? ($record instanceof Model ? $record->getAttribute('type_id') : null);

        if (! $type instanceof Type && $typeId !== null) {
            /** @var class-string<Type> $model */
            $model = Type::class;

            $type = $model::query()->find($typeId);
        }

        $type ??= ResolveRequestedSectionTypeAction::run($configurator->getRawState());

        $adminType = $type instanceof Type
            ? $resolver->resolveForType($type, ConfiguratorTypeEnum::Section, DefaultSectionConfigurator::getKey())
            : DefaultSectionConfigurator::class;

        return $adminType::configure($configurator->columns());
    }
}
