<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Types\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\ContentStructureSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\RequiredFields;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Admin\Filament\Resources\Types\Schemas\Types\DefaultTypeSchema;
use Capell\Layout\Enums\ContentSchemaEnum;
use Capell\Layout\Enums\TypeSchemaEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class ContentTypeSchema extends DefaultTypeSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        return [
            ...$this->settingsSchema($schema),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->adminTab(),
                ]),
            ...$this->statusSchema(),
        ];
    }

    protected function adminTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columnSpanFull()
            ->columns()
            ->schema([
                SchemaSelect::make('schema')
                    ->default(fn (): string => ContentSchemaEnum::Default->name)
                    ->setupOptions(TypeSchemaEnum::Content),
                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),
                ContentStructureSelect::make('content_structure'),
                Group::make([
                    Checkbox::make('required_translation')
                        ->label(__('capell-admin::form.required_translations')),
                    RequiredFields::make()
                        ->visibleJs(<<<'JS'
                             $get('required_translation')
                        JS),
                ]),
            ]);
    }
}
