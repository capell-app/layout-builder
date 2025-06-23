<?php

declare(strict_types=1);

namespace Capell\Layout;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\AssetData;
use Capell\Core\Data\TypeData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Layout\Actions\InstallPackageAction;
use Capell\Layout\Commands\DemoCommand;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Filament\Resources\LayoutResource;
use Capell\Layout\Filament\Schemas;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

class LayoutServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-layout';

    public static string $description = 'Capell Layout & Widgets Package';

    public function bootingPackage(): void
    {
        $this->registerModels()
            ->registerEvents()
            ->registerListeners()
            ->registerRelationships()
            ->registerSchemas()
            ->registerPublishCommands();

        CapellCore::addCloneableRelations('page', 'widgetAssets');
        CapellCore::addDraftableRelations('page', 'widgetAssets');

        Relation::morphMap([
            'content' => Content::class,
            'content_asset' => ContentAsset::class,
            'widget' => Widget::class,
            'widget_asset' => WidgetAsset::class,
        ]);

        foreach (config('capell-layout.livewire_components', []) as $name => $class) {
            Livewire::component($name, $class);
        }

        foreach (config('capell-layout.blade_components') as $name => $component) {
            Blade::component($name, $component);
        }

        $viewPath = base_path('vendor/capell-app/layout/resources/views/capell');

        if (! is_dir($viewPath)) {
            throw new Exception('Theme view path not found: '.$viewPath);
        }

        app('view')->prependNamespace('capell', $viewPath);

        Blade::componentNamespace('Capell\\Layout\\View\\Components', 'capell');
        Blade::anonymousComponentNamespace('Capell\\Layout\\View\\Components');
    }

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasMigrations(CapellLayoutManager::getMigrations())
            ->hasCommands([
                DemoCommand::class,
            ])
            ->hasInstallCommand(
                fn (InstallCommand $command): InstallCommand => $command
                    ->startWith(function (InstallCommand $command): void {
                        $command->info('Installing Capell Layout Package...');
                    })
                    ->publishAssets()
                    ->publishMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call('migrate');

                        InstallPackageAction::run();
                    })
            );
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        App::singleton(CapellLayoutManager::class, fn (): CapellLayoutManager => new CapellLayoutManager());

        CapellCore::registerPackage(
            self::$name,
            self::class,
            permissions: $this->getPackagePermissions(),
            demoCommand: true,
            demoParams: ['sites'],
            publishAssetsCommand: true,
        );

        CapellAdmin::registerResource(
            Enums\LayoutResourceEnum::Content->name,
            class: Enums\LayoutResourceEnum::Content->value,
        );

        CapellAdmin::registerResource(
            Enums\LayoutResourceEnum::Widget->name,
            class: Enums\LayoutResourceEnum::Widget->value,
        );

        CapellAdmin::registerResource(
            ResourceEnum::Layout,
            class: LayoutResource::class,
        );

        foreach (LayoutTypeEnum::cases() as $layoutType) {
            CapellCore::registerType(
                new TypeData(
                    name: $layoutType->value,
                    table: $layoutType->getTable()
                )
            );
        }

        CapellCore::registerAsset(
            new AssetData(
                name: AssetEnum::Content->name,
                model: Content::class,
                icon: 'heroicon-o-document-text',
                component: Enums\AssetComponentEnum::Content->value,
            )
        );
    }

    private function getPackagePermissions(): array
    {
        return [
            'create_content',
            'reorder_content',
            'replicate_content',
            'restore_any_content',
            'restore_content',
            'update_content',
            'view_any_content',
            'view_content',
        ];
    }

    private function registerModels(): self
    {
        CapellCore::registerModel(Enums\LayoutModelEnum::Content, Content::class);
        CapellCore::registerModel(Enums\LayoutModelEnum::ContentAsset, ContentAsset::class);
        CapellCore::registerModel(Enums\LayoutModelEnum::Widget, Widget::class);
        CapellCore::registerModel(Enums\LayoutModelEnum::WidgetAsset, WidgetAsset::class);

        return $this;
    }

    private function registerListeners(): self
    {
        CapellCore::subscriberManager()->subscribe(Listeners\AfterRecordSaved::class);
        CapellCore::subscriberManager()->subscribe(Listeners\SiteTreeRebuilt::class);
        CapellCore::subscriberManager()->subscribe(Listeners\TypeValidated::class);
        CapellCore::subscriberManager()->subscribe(Listeners\LayoutLoaded::class);

        return $this;
    }

    private function registerEvents(): self
    {
        Filament::serving(function (): void {
            $createDeleteModels = [
                CapellCore::getModel(Enums\LayoutModelEnum::Content->name),
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

        return $this;
    }

    private function registerRelationships(): self
    {
        Models\Media::resolveRelationUsing(
            'pages',
            fn (Models\Media $model): HasManyThrough => $model->hasManyThrough(
                Models\Page::class,
                WidgetAsset::class
            )
        );

        Models\Media::resolveRelationUsing(
            'widgets',
            fn (Models\Media $model): MorphToMany => $model->morphToMany(
                Widget::class,
                'asset',
                'widget_assets'
            )
        );

        Models\Page::resolveRelationUsing(
            'media',
            fn (Models\Page $model): HasManyThrough => $model->hasManyThrough(
                Models\Media::class,
                WidgetAsset::class,
                'page_id',
                'id',
                'id',
                'asset_id'
            )
                ->where('widget_assets.asset_type', Models\Media::class)
        );

        Models\Page::resolveRelationUsing(
            'pages',
            fn (Models\Page $model): HasManyThrough => $model->hasManyThrough(
                Models\Page::class,
                WidgetAsset::class,
                'page_id',
                'id',
                'id',
                'asset_id'
            )
                ->where('widget_assets.asset_type', Models\Page::class)
        );

        Models\Page::resolveRelationUsing(
            'widgetAssets',
            fn (Models\Page $model): HasMany => $model->hasMany(WidgetAsset::class)
        );

        Models\Layout::resolveRelationUsing(
            'layoutWidgets',
            fn (Models\Layout $model): BelongsToJson => $model->belongsToJson(
                Widget::class,
                'widgets',
                'key'
            )
        );

        Models\Page::resolveRelationUsing(
            'widgets',
            fn (Models\Page $model): MorphToMany => $model->morphToMany(
                Widget::class,
                'asset',
                'widget_assets')
        );

        Models\Page::resolveRelationUsing(
            'contents',
            fn (Models\Page $model): HasManyThrough => $model->hasManyThrough(
                Content::class,
                WidgetAsset::class,
                'page_id',
                'id',
                'id',
                'asset_id'
            )
                ->where('widget_assets.asset_type', Content::class)
        );

        Models\Tag::resolveRelationUsing(
            'contents',
            fn (Models\Tag $model): MorphToMany => $model->morphedByMany(Content::class, 'taggable')
        );

        Models\Site::resolveRelationUsing(
            'contents',
            fn (Models\Site $model): HasMany => $model->hasMany(Content::class, 'site_id')
        );

        Models\Type::resolveRelationUsing(
            'contents',
            fn (Models\Type $model): HasMany => $model->hasMany(Content::class, 'type_id')
        );

        Models\Type::resolveRelationUsing(
            'widgets',
            fn (Models\Type $model): HasMany => $model->hasMany(Widget::class, 'type_id')
        );

        Models\Type::resolveRelationUsing(
            'widgetType',
            fn (Models\Type $model): Builder => $model->where('type', LayoutTypeEnum::Widget)
        );

        return $this;
    }

    private function registerPublishCommands(): self
    {
        $vendorAssets = $this->package->basePath('/../resources/dist');
        $appAssets = public_path('vendor/'.$this->package->shortName());

        $this->publishes([$vendorAssets => $appAssets], $this->package->shortName().'-assets');

        return $this;
    }

    private function registerSchemas(): self
    {
        CapellAdmin::registerSchemas('Content', Enums\ContentSchemaEnum::cases());
        CapellAdmin::registerSchemas('Widget', Enums\WidgetSchemaEnum::cases());
        CapellAdmin::registerSchemas('WidgetAsset', Enums\WidgetAssetSchemaEnum::cases());
        CapellAdmin::registerSchemas('LayoutContainer', Enums\LayoutContainerSchemaEnum::cases());
        CapellAdmin::registerSchemas('LayoutWidget', Enums\LayoutWidgetSchemaEnum::cases());
        CapellAdmin::registerSchema(SchemaEnum::Layout, Schemas\Layout\DefaultLayoutSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Page, Schemas\Page\DefaultPageSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Page, Schemas\Page\LandingPageSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Page, Schemas\Page\ResultsPageSchema::class);

        return $this;
    }
}
