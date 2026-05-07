<?php

declare(strict_types=1);

namespace Capell\Newsletter\Filament\Resources\NewsletterTags;

use BackedEnum;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Facades\CapellCore;
use Capell\Newsletter\Filament\Resources\NewsletterTags\Pages\CreateNewsletterTag;
use Capell\Newsletter\Filament\Resources\NewsletterTags\Pages\EditNewsletterTag;
use Capell\Newsletter\Filament\Resources\NewsletterTags\Pages\ListNewsletterTags;
use Capell\Newsletter\Providers\NewsletterServiceProvider;
use Capell\Tags\Models\Tag;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class NewsletterTagResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return $configurator->components([
            TextInput::make('name')->label(__('capell-newsletter::form.name'))->required(),
            TextInput::make('slug')->label(__('capell-newsletter::form.handle')),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('capell-newsletter::form.name'))->searchable(),
            TextColumn::make('slug')->label(__('capell-newsletter::form.handle'))->searchable(),
        ]);
    }

    #[Override]
    public static function getModel(): string
    {
        return Tag::class;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return SiteScope::applyForCurrentActor(parent::getEloquentQuery())
            ->where('type', config('capell-newsletter.newsletter_tag_type', 'newsletter'));
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-newsletter::navigation.newsletter_tags');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::isPackageInstalled(NewsletterServiceProvider::$packageName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterTags::route('/'),
            'create' => CreateNewsletterTag::route('/create'),
            'edit' => EditNewsletterTag::route('/{record}/edit'),
        ];
    }
}
