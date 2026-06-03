<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;
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
            $type = $loadedType instanceof Blueprint ? $loadedType : null;
        }

        $typeId = $record instanceof Model
            ? $record->getAttribute('blueprint_id')
            : (self::safeRawState($configurator)['blueprint_id'] ?? null);

        if (! $type instanceof Blueprint && $typeId !== null) {
            /** @var class-string<Blueprint> $model */
            $model = Blueprint::class;

            $type = $model::query()->find($typeId);
        }

        $adminType = $type instanceof Blueprint
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
            $rawState = $configurator->getRawState();

            return $rawState instanceof Arrayable ? $rawState->toArray() : $rawState;
        } catch (ActionNotResolvableException) {
            return null;
        }
    }
}
