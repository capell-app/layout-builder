<?php

declare(strict_types=1);

namespace Capell\Media\Filament\Resources\Media;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Media\Filament\Resources\Media\Pages\ListMedia;
use Capell\Media\Filament\Resources\Media\Tables\MediaTable;
use Capell\Media\Models\Media;
use Capell\Navigation\Filament\Concerns\HasNavigationBadge;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class MediaResource extends Resource
{
    use HasNavigationBadge;
    use HasTableConfigurator;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Photo;

    protected static ?string $recordTitleAttribute = 'file_name';

    /** @var class-string<MediaTable> */
    protected static string $tableConfigurator = MediaTable::class;

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['model']);
    }

    /**
     * @return class-string<SpatieMedia>
     */
    #[Override]
    public static function getModel(): string
    {
        return Media::class;
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.media');
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_content');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
        ];
    }
}
