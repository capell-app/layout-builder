<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Schemas\Widgets;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\CreateDetailsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\SettingsSchema;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetAdminTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Mosaic\Filament\Components\Forms\Widget\TranslationsRepeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Override;

class SystemWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    public function make(Schema $schema): array
    {
        $operation = $schema->getOperation();

        return match ($operation) {
            'createOption', 'editOption',  'replicate' => $this->getOptionSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getFilesSchema(): array
    {
        return [
            DisplaySection::make(),
            ComponentSection::make()
                ->statePath('meta'),
        ];
    }

    protected function getOptionSchema(Schema $schema): array
    {
        return [
            CreateDetailsSchema::make($schema),
            TranslationsRepeater::make($schema)
                ->contained(fn (string $operation): bool => $operation === 'create'),
            ...$this->getFilesSchema(),
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
                    TranslationsRepeater::make($schema)
                        ->contained(),
                ])
                ->sidebarSchema(
                    SettingsSchema::make($schema),
                    contained: true,
                ),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    WidgetDisplayTab::make([
                        ...$this->getFilesSchema(),
                    ]),
                    WidgetAdminTab::make(),
                ]),
        ];
    }
}
