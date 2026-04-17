<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\CacheFrequencySelect;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\ResultsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types\DefaultWidgetSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class RelatedWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getOptionSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            TranslationsRepeater::make($schema)
                ->contained(fn (string $operation): bool => $operation === 'create'),
            Section::make(__('capell-admin::generic.settings'))
                ->columns()
                ->compact()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->collapsed()
                ->schema(SettingsSchema::make($schema)),
        ];
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    TranslationsRepeater::make($schema),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($schema),
                    contained: true,
                ),
            Tabs::make()
                ->visibleOn('edit')
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        DisplaySection::make([
                            Group::make([
                                Checkbox::make('exclude_parent')
                                    ->label(__('capell-mosaic::form.exclude_parent')),
                                Select::make('exclude_types')
                                    ->label(__('capell-mosaic::form.exclude_types'))
                                    ->helperText(__('capell-mosaic::generic.exclude_types_info'))
                                    ->multiple()
                                    ->options(
                                        function (): array {
                                            /** @var class-string<Type> $model */
                                            $model = CapellCore::getModel(ModelEnum::Type);

                                            return $model::query()
                                                ->pageType()
                                                ->pluck('name', 'key')
                                                ->toArray();
                                        },
                                    ),
                            ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('limit')
                                        ->label(__('capell-mosaic::form.limit')),
                                    Checkbox::make('pagination')
                                        ->label(__('capell-mosaic::form.pagination'))
                                        ->default(true),
                                    CacheFrequencySelect::make('cache_frequency'),
                                ]),
                            ...ResultsSchema::make($schema),
                        ]),
                        ComponentSection::make()
                            ->statePath('meta'),
                    ]),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
