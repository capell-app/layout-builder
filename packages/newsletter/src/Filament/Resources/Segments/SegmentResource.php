<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\Segments;

use BackedEnum;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Enums\SegmentType;
use Capell\Newsletter\Filament\Concerns\ScopesNewsletterResourcesToAssignedSites;
use Capell\Newsletter\Filament\Resources\Segments\Pages\CreateSegment;
use Capell\Newsletter\Filament\Resources\Segments\Pages\EditSegment;
use Capell\Newsletter\Filament\Resources\Segments\Pages\ListSegments;
use Capell\Newsletter\Models\Segment;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class SegmentResource extends Resource
{
    use ScopesNewsletterResourcesToAssignedSites;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return $configurator->components([
            SiteSelect::make('site_id')->required(),
            TextInput::make('name')->label(__('capell-newsletter::form.name'))->required(),
            TextInput::make('handle')->label(__('capell-newsletter::form.handle'))->required(),
            Select::make('type')
                ->options(self::segmentTypeOptions())
                ->required(),
            KeyValue::make('filters'),
            Toggle::make('is_active')->label(__('capell-newsletter::form.active'))->default(true),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('capell-newsletter::form.name'))->searchable()->sortable(),
            TextColumn::make('handle')->label(__('capell-newsletter::form.handle'))->searchable(),
            TextColumn::make('type')->badge()->sortable(),
            TextColumn::make('updated_at')->label(__('capell-newsletter::table.updated_at'))->dateTime()->sortable(),
        ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return Segment::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return self::applyNewsletterSiteScope(parent::getEloquentQuery());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.segments');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSegments::route('/'),
            'create' => CreateSegment::route('/create'),
            'edit' => EditSegment::route('/{record}/edit'),
        ];
    }

    private static function segmentTypeOptions(): array
    {
        return collect(SegmentType::cases())
            ->mapWithKeys(static fn (SegmentType $type): array => [$type->value => $type->getLabel()])
            ->all();
    }
}
