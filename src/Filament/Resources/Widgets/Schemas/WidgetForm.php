<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Models\Type;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class WidgetForm implements FormConfigurator
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

        $typeId = $record instanceof Model
            ? $record->getAttribute('blueprint_id')
            : (self::safeRawState($configurator)['blueprint_id'] ?? null);

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

    /**
     * @return array<string, mixed>|null
     */
    private static function safeRawState(Schema $configurator): ?array
    {
        try {
            return $configurator->getRawState();
        } catch (ActionNotResolvableException) {
            return null;
        }
    }
}
