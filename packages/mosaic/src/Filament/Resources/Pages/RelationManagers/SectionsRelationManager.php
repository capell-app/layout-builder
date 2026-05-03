<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Pages\RelationManagers;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Resources\Sections\Schemas\SectionForm;
use Capell\Mosaic\Filament\Resources\Sections\Tables\SectionsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SectionsRelationManager extends RelationManager
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasRelationManagerBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'sections';

    protected static string $formConfigurator = SectionForm::class;

    protected static string $tableConfigurator = SectionsTable::class;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::generic.sections');
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|null
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Section)::getNavigationIcon();
    }

    public function form(Schema $configurator): Schema
    {
        return static::getFormConfigurator()::configure($configurator);
    }

    public function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }
}
