<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Resources\ContentBlocks\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\ContentBlocks\Actions\ResolveRequestedContentBlockTypeAction;
use Capell\ContentBlocks\Enums\ConfiguratorTypeEnum;
use Capell\ContentBlocks\Filament\Configurators\ContentBlocks\DefaultContentBlockConfigurator;
use Capell\Core\Models\Type;
use Filament\Schemas\Schema;

class ContentBlockForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();
        $type = null;

        if ($record?->relationLoaded('type') && $record->type instanceof Type) {
            $type = $record->type;
        }

        $state = $configurator->getRawState();
        $typeId = $state['type_id'] ?? $record?->type_id ?? null;

        if (! $type instanceof Type && $typeId !== null) {
            /** @var class-string<Type> $model */
            $model = Type::class;

            $type = $model::query()->find($typeId);
        }

        if (! $type instanceof Type) {
            $type = ResolveRequestedContentBlockTypeAction::run($state);
        }

        $adminType = $type instanceof Type
            ? $resolver->resolveForType($type, ConfiguratorTypeEnum::ContentBlock, DefaultContentBlockConfigurator::getKey())
            : DefaultContentBlockConfigurator::class;

        return $adminType::configure($configurator->columns());
    }
}
