<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Widget;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Enums\ConfiguratorTypeEnum as AdminConfiguratorTypeEnum;
use Capell\Admin\Filament\Components\Forms\BlueprintSelect as BaseBlueprintSelect;
use Capell\Core\Enums\BlueprintSubjectEnum;
use Capell\LayoutBuilder\Enums\LayoutTypeEnum;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;

class TypeSelect extends BaseBlueprintSelect
{
    protected null|BlueprintSubjectEnum|string $type = LayoutTypeEnum::Widget->value;

    #[Override]
    protected function setUp(?string $label = null): void
    {
        parent::setUp($label);

        $this->disableOptionWhen(
            fn (self $component, mixed $value, string $operation): bool => $operation === 'edit'
                && (string) $value !== (string) $component->getState(),
        );
    }

    #[Override]
    public function withRelation(): static
    {
        parent::withRelation();

        $this->saveRelationshipsUsing(function (self $component, string $operation): void {
            if ($operation === 'edit') {
                return;
            }

            $component->saveStateToRelationship();
        });

        return $this;
    }

    #[Override]
    public function withEditForm(): static
    {
        parent::withEditForm();

        return $this
            ->editOptionForm(fn (Schema $schema): Schema => WidgetTypeConfigurator::configure(
                $schema,
                ConfiguratorContextData::forEdit(
                    target: AdminConfiguratorTypeEnum::Blueprint,
                    resourceName: WidgetTypeConfigurator::getKey(),
                ),
            ))
            ->updateOptionUsing(static function (array $data, Schema $schema): void {
                $record = $schema->getRecord();

                if (! $record instanceof Model) {
                    return;
                }

                $data['type'] = LayoutTypeEnum::Widget->value;
                $data = self::mergeNestedBlueprintData($record, $data, 'admin');
                $data = self::mergeNestedBlueprintData($record, $data, 'meta');

                $record->update($data);
            });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function mergeNestedBlueprintData(Model $record, array $data, string $key): array
    {
        $existing = $record->getAttribute($key);

        if (isset($data[$key]) && is_array($data[$key]) && is_array($existing)) {
            $data[$key] = array_replace_recursive($existing, $data[$key]);
        }

        return $data;
    }
}
