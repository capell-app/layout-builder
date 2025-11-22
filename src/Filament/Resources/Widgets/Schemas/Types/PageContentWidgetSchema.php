<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\HeadingSizeSelect;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PageContentWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = SchemaTypeEnum::Widget;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Widget->value);
    }

    public function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'editOption', 'replicate' => $this->getOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getTabs(): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                WidgetDisplayTab::make([
                    Group::make()
                        ->columns()
                        ->schema([
                            Grid::make()
                                ->statePath('meta')
                                ->schema([
                                    CheckboxList::make('page_content')
                                        ->label(__('capell-layout::form.page_content'))
                                        ->helperText(__('capell-admin::generic.widget_page_content_helper'))
                                        ->reactive()
                                        ->columns(3)
                                        ->options([
                                            'title' => __('capell-admin::generic.title'),
                                            'content' => __('capell-admin::generic.content'),
                                            'contents' => __('capell-admin::generic.contents'),
                                        ]),
                                    HeadingSizeSelect::make('heading_size')
                                        ->visible(
                                            fn (Get $get): bool => in_array(
                                                'title',
                                                $get('page_content') ?: [],
                                                true,
                                            ),
                                        ),
                                ]),
                            WidgetDisplaySection::make(),
                            WidgetComponentFilesSection::make()
                                ->statePath('meta'),
                        ]),
                ]),
                WidgetAdminTab::make(),
            ]);
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    $this->getTabs(),
                ])
                ->sidebarSchema(
                    WidgetSettingsSchema::make($schema),
                    contained: true,
                ),
        ];
    }

    protected function getOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            $this->getTabs(),
        ];
    }
}
