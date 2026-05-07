<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PreviewLinks;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\Pages\ManagePreviewLinks;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\Tables\PreviewLinksTable;
use Capell\PublishingStudio\Models\PreviewLink;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class PreviewLinkResource extends Resource
{
    use HasConfiguredTable;

    protected static ?string $model = PreviewLink::class;

    protected static string $tableConfigurator = PreviewLinksTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Link;

    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_content');
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.preview_links');
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return (string) __('capell-admin::workspace.preview_link.singular');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return (string) __('capell-admin::workspace.preview_link.plural');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$navigationIcon;
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return static::$activeNavigationIcon;
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ManagePreviewLinks::route('/'),
        ];
    }
}
