<?php

declare(strict_types=1);

namespace Capell\Layout;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Data\AssetData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\WidgetSchemaEnum;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\LaravelPackageTools\Package;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class LayoutServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-layout';

    public function bootingPackage(): void
    {
        $this->registerModels();

        $this->registerRelationships();

        CapellAdmin::registerSchemas('Widget', WidgetSchemaEnum::getAllSchemas());

        CapellCore::addCloneableRelations('page', 'widgetAssets');

        CapellCore::addDraftableRelations('page', 'widgetAssets');

        Relation::morphMap([
            'content' => Content::class,
            'content_asset' => ContentAsset::class,
            'widget' => Widget::class,
            'widget_asset' => WidgetAsset::class,
        ]);

        Filament::serving(function (): void {
            $createDeleteModels = [
                CapellCore::getModel(LayoutModelEnum::Content->name),
            ];

            foreach ($createDeleteModels as $modelClass) {
                $modelClass::registerModelEvent('created', function (Model $model): void {
                    CreatedModelAction::run($model);
                });

                $modelClass::registerModelEvent('deleted', function (Model $model): void {
                    DeletedModelAction::run($model);
                });
            }
        });
    }

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasMigrations(CapellLayoutManager::getMigrations());
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        CapellCore::registerPackage(self::$name, self::class);

        CapellAdmin::registerAsset(new AssetData(name: 'content', model: Content::class, icon: 'heroicon-o-document-text'));
    }

    private function registerModels(): void
    {
        CapellCore::registerModel(LayoutModelEnum::Content, Content::class);
        CapellCore::registerModel(LayoutModelEnum::ContentAsset, ContentAsset::class);
        CapellCore::registerModel(LayoutModelEnum::Widget, Widget::class);
        CapellCore::registerModel(LayoutModelEnum::WidgetAsset, WidgetAsset::class);
    }

    private function registerRelationships(): void
    {
        Models\Media::resolveRelationUsing('pages', fn (Models\Media $model): HasManyThrough => $model->hasManyThrough(Models\Page::class, WidgetAsset::class));

        Models\Media::resolveRelationUsing('widgets', fn (Models\Media $model): MorphToMany => $model->morphToMany(Widget::class, 'asset', 'widget_assets'));

        Models\Page::resolveRelationUsing('media', fn (Models\Page $model): HasManyThrough => $model->hasManyThrough(
            Models\Media::class,
            WidgetAsset::class,
            'page_id',
            'id',
            'id',
            'asset_id'
        )
            ->where('widget_assets.asset_type', Models\Media::class));

        Models\Page::resolveRelationUsing('pages', fn (Models\Page $model): HasManyThrough => $model->hasManyThrough(
            Models\Page::class,
            WidgetAsset::class,
            'page_id',
            'id',
            'id',
            'asset_id'
        )
            ->where('widget_assets.asset_type', Models\Page::class));

        Models\Page::resolveRelationUsing('widgetAssets', fn (Models\Page $model): HasMany => $model->hasMany(WidgetAsset::class));

        Models\Layout::resolveRelationUsing('layoutWidgets', fn (Models\Layout $model): BelongsToJson => $model->belongsToJson(Widget::class, 'widgets', 'key'));

        Models\Page::resolveRelationUsing('widgets', fn (Models\Page $model): MorphToMany => $model->morphToMany(Widget::class, 'asset', 'widget_assets'));

        Models\Page::resolveRelationUsing('contents', fn (Models\Page $model): HasManyThrough => $model->hasManyThrough(
            Content::class,
            WidgetAsset::class,
            'page_id',
            'id',
            'id',
            'asset_id'
        )
            ->where('widget_assets.asset_type', Content::class));

        Models\Tag::resolveRelationUsing('contents', fn (Models\Tag $model): MorphToMany => $model->morphedByMany(Content::class, 'taggable'));

        Models\Site::resolveRelationUsing('contents', fn (Models\Site $model): HasMany => $model->hasMany(Content::class, 'layout_id'));

        Models\Type::resolveRelationUsing('contents', fn (Models\Type $model): HasMany => $model->hasMany(Content::class, 'type_id'));

        Models\Type::resolveRelationUsing('widgets', fn (Models\Type $model): HasMany => $model->hasMany(Widget::class, 'type_id'));

        Models\Type::resolveRelationUsing('widgetType', fn (Models\Type $model): Builder => $model->where('type', 'widget'));
    }
}
