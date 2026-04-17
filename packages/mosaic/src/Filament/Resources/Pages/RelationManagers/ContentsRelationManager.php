<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Pages\RelationManagers;

use BackedEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Mosaic\Enums\ResourceEnum;
use Capell\Mosaic\Filament\Resources\Contents\Schemas\ContentForm;
use Capell\Mosaic\Filament\Resources\Contents\Tables\ContentsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContentsRelationManager extends RelationManager
{
    use HasFormConfigurator;
    use HasRelationManagerBadge;
    use HasTableConfigurator;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'contents';

    protected static string $formConfigurator = ContentForm::class;

    protected static string $tableConfigurator = ContentsTable::class;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::generic.contents');
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|null
    {
        return CapellAdmin::getResource(ResourceEnum::Content)::getNavigationIcon();
    }

    public function form(Schema $schema): Schema
    {
        return static::getFormConfigurator()::configure($schema);
    }

    public function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }
}
