<?php

declare(strict_types=1);

namespace Capell\Layout;

use Capell\Admin\Actions\CreatedModelAction;
use Capell\Admin\Actions\DeletedModelAction;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Enums\SchemaEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Forms\Editor\RichEditor;
use Capell\Core\Data\AssetData;
use Capell\Core\Data\TypeData;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Core\Models\Site;
use Capell\Core\Models\Tag;
use Capell\Core\Models\Type;
use Capell\Core\Packages\AbstractPackageServiceProvider;
use Capell\Layout\Actions\InstallPackageAction;
use Capell\Layout\Commands\DemoCommand;
use Capell\Layout\Enums\AssetComponentEnum;
use Capell\Layout\Enums\AssetEnum;
use Capell\Layout\Enums\ComponentTypeEnum;
use Capell\Layout\Enums\ContentSchemaEnum;
use Capell\Layout\Enums\LayoutContainerSchemaEnum;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Enums\LayoutTypeEnum;
use Capell\Layout\Enums\LayoutWidgetSchemaEnum;
use Capell\Layout\Enums\WidgetAssetSchemaEnum;
use Capell\Layout\Enums\WidgetSchemaEnum;
use Capell\Layout\Filament\Resources\LayoutResource;
use Capell\Layout\Filament\Schemas\Layout\DefaultLayoutSchema;
use Capell\Layout\Filament\Schemas\Page\DefaultPageSchema;
use Capell\Layout\Filament\Schemas\Page\LandingPageSchema;
use Capell\Layout\Filament\Schemas\Page\ResultsPageSchema;
use Capell\Layout\Filament\Schemas\Type\WidgetTypeSchema;
use Capell\Layout\Listeners\AfterRecordSaved;
use Capell\Layout\Listeners\LayoutLoaded;
use Capell\Layout\Listeners\SiteTreeRebuilt;
use Capell\Layout\Listeners\TypeValidated;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Exception;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use RuntimeException;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;
use Staudenmeir\EloquentJsonRelations\Relations\HasManyJson;

class LayoutServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-layout';

    public static string $description = 'Managing content and widgets.';

    public function bootingPackage(): void
    {
        $this->registerModels()
            ->registerEvents()
            ->registerListeners()
            ->registerRelationships()
            ->registerSchemas()
            ->registerSchemaHooks()
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

        $viewPath = realpath(__DIR__.'/../resources/views/capell');

        if (! $viewPath || ! is_dir($viewPath)) {
            throw new Exception('Theme view path not found: '.$viewPath);
        }

        app('view')->prependNamespace('capell', $viewPath);

        Blade::componentNamespace('Capell\\Layout\\View\\Components', 'capell-layout');
        Blade::anonymousComponentNamespace('Capell\\Layout\\View\\Components');

        $publishDir = self::getPublishedDirectory();

        FilamentAsset::register([
            AlpineComponent::make('layout-builder', $publishDir.'/build/layout-builder.js')
                ->loadedOnRequest(),
        ],
            package: 'capell-layout'
        );
    }

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasCommands([
                DemoCommand::class,
            ])
            ->hasInstallCommand(
                fn (InstallCommand $command): InstallCommand => $command
                    ->startWith(function (InstallCommand $command): void {
                        $command->info('Installing Capell Layout Package...');
                    })
                    ->publishAssets()
                    ->endWith(function (InstallCommand $command): void {
                        $command->call(
                            'capell:publish-migrations',
                            ['--migrations' => CapellLayoutManager::getMigrations(), '--path' => __DIR__.'/../database/migrations']
                        );

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
            class: self::class,
            path: __DIR__,
            permissions: $this->getPackagePermissions(),
            demoCommand: true,
            demoParams: ['author', 'sites'],
            publishAssetsCommand: true,
        );

        CapellAdmin::registerResource(
            LayoutResourceEnum::Content->name,
            class: LayoutResourceEnum::Content->value,
        );

        CapellAdmin::registerResource(
            LayoutResourceEnum::Widget->name,
            class: LayoutResourceEnum::Widget->value,
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

        foreach (ComponentTypeEnum::cases() as $componentType) {
            CapellCore::registerComponents($componentType->name, $componentType->value::cases());
        }

        CapellCore::registerAsset(
            new AssetData(
                name: AssetEnum::Content->name,
                model: Content::class,
                icon: 'heroicon-o-document-text',
                component: AssetComponentEnum::Content->value,
                hasTranslation: true,
            )
        );
    }

    protected function getPublishedDirectory(): string
    {
        $dir = realpath(__DIR__.'/../publishes');

        if (! $dir) {
            throw new RuntimeException('Publish directory not found.');
        }

        return $dir;
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
        CapellCore::registerModel(LayoutModelEnum::Content, Content::class);
        CapellCore::registerModel(LayoutModelEnum::ContentAsset, ContentAsset::class);
        CapellCore::registerModel(LayoutModelEnum::Widget, Widget::class);
        CapellCore::registerModel(LayoutModelEnum::WidgetAsset, WidgetAsset::class);

        return $this;
    }

    private function registerListeners(): self
    {
        CapellCore::subscriberManager()->subscribe(AfterRecordSaved::class);
        CapellCore::subscriberManager()->subscribe(SiteTreeRebuilt::class);
        CapellCore::subscriberManager()->subscribe(TypeValidated::class);
        CapellCore::subscriberManager()->subscribe(LayoutLoaded::class);

        return $this;
    }

    private function registerEvents(): self
    {
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

        return $this;
    }

    private function registerRelationships(): self
    {
        Media::resolveRelationUsing(
            'pages',
            fn (Media $model): HasManyThrough => $model->hasManyThrough(
                Page::class,
                WidgetAsset::class
            )
        );

        Media::resolveRelationUsing(
            'widgets',
            fn (Media $model): MorphToMany => $model->morphToMany(
                Widget::class,
                'asset',
                'widget_assets'
            )
        );

        Page::resolveRelationUsing(
            'media',
            fn (Page $model): HasManyThrough => $model->hasManyThrough(
                Media::class,
                WidgetAsset::class,
                'page_id',
                'id',
                'id',
                'asset_id'
            )
                ->where('widget_assets.asset_type', app(Media::class)->getMorphClass())
        );

        Page::resolveRelationUsing(
            'pages',
            fn (Page $model): HasManyThrough => $model->hasManyThrough(
                Page::class,
                WidgetAsset::class,
                'page_id',
                'id',
                'id',
                'asset_id'
            )
                ->where('widget_assets.asset_type', app(Page::class)->getMorphClass())
        );

        Page::resolveRelationUsing(
            'widgetAssets',
            fn (Page $model): HasMany => $model->hasMany(WidgetAsset::class)
        );

        Layout::resolveRelationUsing(
            'layoutWidgets',
            fn (Layout $model): BelongsToJson => $model->belongsToJson(
                Widget::class,
                'widgets',
                'key'
            )
        );

        Page::resolveRelationUsing(
            'widgets',
            fn (Page $model): MorphToMany => $model->morphToMany(
                Widget::class,
                'asset',
                'widget_assets')
        );

        Page::resolveRelationUsing(
            'contents',
            fn (Page $model): HasManyJson => $model->hasManyJson(
                Content::class,
                'meta->page_id',
                'id',
            )
        );

        Tag::resolveRelationUsing(
            'contents',
            fn (Tag $model): MorphToMany => $model->morphedByMany(Content::class, 'taggable')
        );

        Site::resolveRelationUsing(
            'contents',
            fn (Site $model): HasMany => $model->hasMany(Content::class, 'site_id')
        );

        Type::resolveRelationUsing(
            'contents',
            fn (Type $model): HasMany => $model->hasMany(Content::class, 'type_id')
        );

        Type::resolveRelationUsing(
            'widgets',
            fn (Type $model): HasMany => $model->hasMany(Widget::class, 'type_id')
        );

        Type::resolveRelationUsing(
            'widgetType',
            fn (Type $model): Builder => $model->where('type', LayoutTypeEnum::Widget)
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
        CapellAdmin::registerSchemas('Content', ContentSchemaEnum::cases());
        CapellAdmin::registerSchemas('Widget', WidgetSchemaEnum::cases());
        CapellAdmin::registerSchemas('WidgetAsset', WidgetAssetSchemaEnum::cases());
        CapellAdmin::registerSchemas('LayoutContainer', LayoutContainerSchemaEnum::cases());
        CapellAdmin::registerSchemas('LayoutWidget', LayoutWidgetSchemaEnum::cases());
        CapellAdmin::registerSchema(SchemaEnum::Type, WidgetTypeSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Layout, DefaultLayoutSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Page, DefaultPageSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Page, LandingPageSchema::class);
        CapellAdmin::registerSchema(SchemaEnum::Page, ResultsPageSchema::class);

        return $this;
    }

    private function registerSchemaHooks(): static
    {
        CapellAdmin::registerSchemaHook(
            SchemaEnum::Page->value,
            'translations.contents.before',
            fn (Schema $schema, array $context = []): array => [
                Group::make()
                    ->statePath('meta')
                    ->visible(
                        function (?PageTranslation $record, Get $get, string $operation) use ($schema): bool {
                            if (in_array($operation, ['create', 'createOption'], true)) {
                                return false;
                            }

                            $layoutId = $get('../../../layout_id')
                                ?: ($schema?->getRawState()['layout_id'] ?? null)
                                    ?: $record?->page->layout_id
                                        ?: null;

                            if (! $layoutId) {
                                return false;
                            }

                            $layout = CapellCore::getModel(ModelEnum::Layout)::find($layoutId);

                            if (! in_array('hero', $layout->widgets, true)) {
                                return false;
                            }

                            return true;
                        }
                    )
                    ->schema([
                        RichEditor::make('hero')
                            ->label(__('capell-layout::form.hero'))
                            ->helperText(__('capell-layout::generic.hero_info')),
                    ]),
            ]
        );

        return $this;
    }
}
