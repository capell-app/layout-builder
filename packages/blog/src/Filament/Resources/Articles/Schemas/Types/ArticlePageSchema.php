<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\MediaLibraryFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\PagePublishSection;
use Capell\Admin\Filament\Components\Forms\Page\PageSettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\PageTagsInput;
use Capell\Admin\Filament\Components\Forms\PublishDates;
use Capell\Admin\Filament\Components\Forms\PublishToggle;
use Capell\Admin\Filament\Resources\Pages\RelationManagers\AuditsRelationManager;
use Capell\Admin\Filament\Resources\Pages\Schemas\Types\DefaultPageSchema;
use Capell\Core\Enums\LayoutGroupEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class ArticlePageSchema extends DefaultPageSchema
{
    public static function relationManagers(Model $record): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    protected static function getCreateFormSchema(Schema $schema): array
    {
        return [
            static::getTranslationFormSchema($schema),
            Group::make()
                ->columnSpanFull()
                ->schema([
                    PublishToggle::make('is_published')
                        ->reactive(),
                    PublishDates::make()
                        ->columnSpanFull()
                        ->columns()
                        ->whenFalsy('is_published'),
                ]),
        ];
    }

    protected static function getCreateOptionFormSchema(Schema $schema): array
    {
        return static::getCreateFormSchema($schema);
    }

    protected static function getEditFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    static::getTranslationFormSchema($schema),
                ])
                ->sidebarSchema([
                    Section::make()
                        ->schema([
                            ...PageSettingsSchema::make(
                                $schema,
                                [
                                    PageTagsInput::make('tags'),

                                    Group::make()
                                        ->statePath('meta')
                                        ->schema([
                                            MediaLibraryFileUpload::make('image'),
                                            Select::make('author_id')
                                                ->label(__('capell-admin::form.author'))
                                                ->relationship(name: 'author', titleAttribute: 'name')
                                                ->dehydrated()
                                                ->saveRelationshipsUsing(fn (): false => false),
                                        ]),
                                ],
                                resourceName: 'article',
                            ),
                        ]),
                ]),
        ];
    }

    protected static function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            static::getTranslationFormSchema($schema),
            Section::make(__('capell-admin::generic.settings'))
                ->columns()
                ->compact()
                ->collapsed()
                ->schema([
                    ...PageSettingsSchema::make(
                        $schema,
                        [
                            PageTagsInput::make('tags'),

                            Group::make()
                                ->statePath('meta')
                                ->schema([
                                    MediaLibraryFileUpload::make('image'),
                                    Select::make('author_id')
                                        ->label(__('capell-admin::form.author'))
                                        ->relationship(name: 'author', titleAttribute: 'name')
                                        ->dehydrated()
                                        ->saveRelationshipsUsing(fn (): false => false),
                                ]),
                        ],
                        resourceName: 'article',
                    ),
                    PagePublishSection::make(),
                ]),
        ];
    }

    #[Override]
    protected static function getCreateExtraFor(Schema $schema): array
    {
        return [
            Group::make([
                Hidden::make('is_layout_changed_manually')
                    ->default(false)
                    ->dehydrated(false),

                LayoutSelect::make('layout_id')
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                        $set('is_layout_changed_manually', (bool) $state);
                    })
                    ->modifyQueryUsing(
                        fn (Builder $query, Get $get): Builder => $query->where(
                            fn (Builder $query) => $query->where('group', '!=', LayoutGroupEnum::System)
                                ->orWhereNull('group')
                        )
                    ),
            ]),
            PublishToggle::make('is_published')
                ->reactive(),
            PublishDates::make()
                ->columnSpanFull()
                ->columns()
                ->whenFalsy('is_published'),
        ];
    }
}
