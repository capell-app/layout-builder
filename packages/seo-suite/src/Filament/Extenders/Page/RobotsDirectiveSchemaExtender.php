<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Filament\FormBuilder\Components\CheckboxList;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RobotsDirectiveSchemaExtender implements PageSchemaExtender
{
    /**
     * @return array<int, Component>
     */
    public function extendSettingsTabComponents(): array
    {
        return [
            CheckboxList::make('robots')
                ->options(RobotsDirectiveEnum::class)
                ->descriptions(
                    collect(RobotsDirectiveEnum::cases())
                        ->mapWithKeys(fn (RobotsDirectiveEnum $case): array => [$case->value => $case->getDescription()])
                        ->all(),
                ),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    /**
     * @param  array<int, mixed>  $relationManagers
     * @return array<int, mixed>
     */
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    /**
     * @param  array<int, mixed>  $tabs
     * @return array<int, mixed>
     */
    public function extendTabs(Schema $configurator, array $tabs): array
    {
        return $tabs;
    }
}
