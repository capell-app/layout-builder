<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Schemas\Page;

use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\ImageMediaPicker;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\PagePublishSection;
use Capell\Admin\Filament\Components\Forms\Page\PageSettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\PageTagsInput;
use Capell\Admin\Filament\Components\Forms\PublishToggle;
use Capell\Admin\Filament\Resources\PageResource\RelationManagers\AuditsRelationManager;
use Capell\Admin\Filament\Schemas\Page\DefaultPageSchema;
use Capell\Core\Enums\LayoutGroupEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
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
            Section::make()
                ->contained(in_array($schema->getOperation(), ['create', 'edit']))
                ->columns()
                ->columnSpanFull()
                ->schema([
                    PublishToggle::make('is_published'),
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
                        ->columns(['default' => 1, 'sm' => 2, 'lg' => 1])
                        ->schema([
                            ...PageSettingsSchema::make(
                                $schema,
                                [
                                    PageTagsInput::make('tags'),

                                    Group::make()
                                        ->statePath('meta')
                                        ->mutateDehydratedStateUsing(function (array $state): array {
                                            if (isset($state['image_id'])) {
                                                $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                                            }

                                            return $state;
                                        })
                                        ->schema([
                                            ImageMediaPicker::make('image_id'),
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
                                ->mutateDehydratedStateUsing(function (array $state): array {
                                    if (isset($state['image_id'])) {
                                        $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                                    }

                                    return $state;
                                })
                                ->schema([
                                    ImageMediaPicker::make('image_id'),
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
    protected static function getCreateExtraFor(): array
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
                        fn (Builder $query, Get $get): Builder => $query->when(
                            ! $get('is_system'),
                            fn (Builder $query): Builder => $query->where(
                                fn (Builder $query) => $query->where('group', '!=', LayoutGroupEnum::System)
                                    ->orWhereNull('group')
                            )
                        )
                    ),
            ]),
            Group::make([
                PublishToggle::make('is_published'),
                Toggle::make('is_system')
                    ->label(__('capell-admin::form.system_page'))
                    ->dehydrated(false)
                    ->default(false)
                    ->hidden(fn (): bool => Filament::auth()->user()->hasRole(Utils::getSuperAdminName()))
                    ->reactive(),
            ]),
        ];
    }
}
