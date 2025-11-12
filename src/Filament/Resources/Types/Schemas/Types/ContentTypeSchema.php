<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Types\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\ContentEditorSelect;
use Capell\Admin\Filament\Components\Forms\ContentPresenterSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\SchemaSelect;
use Capell\Admin\Filament\Resources\Types\Schemas\Types\DefaultTypeSchema;
use Capell\Layout\Enums\ContentSchemaEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class ContentTypeSchema extends DefaultTypeSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        return [
            ...$this->getSettingsSchema($schema),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    $this->getFrontendTab(),
                    $this->getAdminTab(),
                ]),
            ...$this->getStatusSchema(),
        ];
    }

    protected function getAdminTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.admin'))
            ->statePath('admin')
            ->icon(config('capell-admin.icon.admin'))
            ->columnSpanFull()
            ->columns()
            ->schema([
                SchemaSelect::make('schema')
                    ->default(fn (): string => ContentSchemaEnum::Default->name)
                    ->setupOptions(SchemaTypeEnum::Content->value),

                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),

                ContentEditorSelect::make('content_editor'),
            ]);
    }

    protected function getFrontendTab(): Tab
    {
        return Tab::make(__('capell-admin::generic.frontend'))
            ->statePath('meta')
            ->icon(Heroicon::OutlinedBuildingStorefront)
            ->columns()
            ->schema([
                ContentPresenterSelect::make(),
            ]);
    }
}
