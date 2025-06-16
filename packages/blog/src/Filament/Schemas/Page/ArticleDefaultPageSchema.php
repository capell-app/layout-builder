<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Schemas\Page;

use BezhanSalleh\FilamentShield\Support\Utils;
use Capell\Admin\Actions\FixCuratorMetaDataAction;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\ImageMediaPicker;
use Capell\Admin\Filament\Components\Forms\Page\LayoutSelect;
use Capell\Admin\Filament\Components\Forms\Page\PagePublishSection;
use Capell\Admin\Filament\Components\Forms\Page\PageSettingsSchema;
use Capell\Admin\Filament\Components\Forms\Page\PageTagsInput;
use Capell\Admin\Filament\Components\Forms\PublishToggle;
use Capell\Admin\Filament\Schemas\Page\DefaultPageSchema;
use Filament\Facades\Filament;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ArticleDefaultPageSchema extends DefaultPageSchema
{
    protected static function getCreateFormSchema(Forms\Form $form): array
    {
        return [
            static::getTranslationFormSchema($form),
            Forms\Components\Section::make()
                ->contained($form->getOperation() !== 'create')
                ->columns()
                ->columnSpanFull()
                ->schema([
                    PublishToggle::make('is_published'),
                ]),
        ];
    }

    protected static function getCreateOptionFormSchema(Forms\Form $form): array
    {
        return static::getCreateFormSchema($form);
    }

    protected static function getEditFormSchema(Forms\Form $form): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema([
                    static::getTranslationFormSchema($form),
                ])
                ->sidebarSchema([
                    Forms\Components\Section::make()
                        ->columns(['default' => 1, 'sm' => 2, 'lg' => 1])
                        ->schema([
                            ...PageSettingsSchema::make(
                                $form,
                                schema: [
                                    PageTagsInput::make('tags'),

                                    Forms\Components\Group::make()
                                        ->statePath('meta')
                                        ->mutateDehydratedStateUsing(function (array $state): array {
                                            if (isset($state['image_id'])) {
                                                $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                                            }

                                            return $state;
                                        })
                                        ->schema([
                                            ImageMediaPicker::make('image_id'),
                                            Forms\Components\Select::make('author_id')
                                                ->label(__('capell-admin::form.author'))
                                                ->relationship(name: 'author', titleAttribute: 'name')
                                                ->dehydrated()
                                                ->saveRelationshipsUsing(fn (): false => false),
                                        ]),
                                ],
                                resourceName: 'article',
                                withParent: false,
                            ),
                            PagePublishSection::make(),
                        ]),
                ]),
        ];
    }

    protected static function getEditOptionFormSchema(Forms\Form $form): array
    {
        return [
            static::getTranslationFormSchema($form),
            Forms\Components\Section::make(__('capell-admin::generic.settings'))
                ->columns()
                ->compact()
                ->collapsed()
                ->schema([
                    ...PageSettingsSchema::make(
                        $form,
                        schema: [
                            PageTagsInput::make('tags'),

                            Forms\Components\Group::make()
                                ->statePath('meta')
                                ->mutateDehydratedStateUsing(function (array $state): array {
                                    if (isset($state['image_id'])) {
                                        $state['image_id'] = FixCuratorMetaDataAction::run($state['image_id']);
                                    }

                                    return $state;
                                })
                                ->schema([
                                    ImageMediaPicker::make('image_id'),
                                    Forms\Components\Select::make('author_id')
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
            Forms\Components\Group::make([
                Forms\Components\Hidden::make('is_layout_changed_manually')
                    ->default(false)
                    ->dehydrated(false),

                LayoutSelect::make('layout_id')
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?int $state): void {
                        $set('is_layout_changed_manually', (bool) $state);
                    })
                    ->modifyQueryUsing(
                        fn (Builder $query, Forms\Get $get): Builder => $query->when(
                            ! $get('is_system'),
                            fn (Builder $query): Builder => $query->where(
                                fn (Builder $query) => $query->where('group', '!=', 'system')
                                    ->orWhereNull('group')
                            )
                        )
                    ),
            ]),
            Forms\Components\Group::make([
                PublishToggle::make('is_published'),
                Forms\Components\Toggle::make('is_system')
                    ->label(__('capell-admin::form.system_page'))
                    ->dehydrated(false)
                    ->default(false)
                    ->hidden(fn (): bool => Filament::auth()->user()->hasRole(Utils::getSuperAdminName()))
                    ->reactive(),
            ]),
        ];
    }
}
