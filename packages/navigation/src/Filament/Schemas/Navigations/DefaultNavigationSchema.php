<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Schemas\Navigations;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Filament\Components\Forms\CustomSelectGroup;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\NameInput;
use Capell\Admin\Filament\Components\Forms\PageMorphToOptionSelect;
use Capell\Admin\Filament\Components\Forms\PublishSection;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageVariationEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Core\Support\Slug\SlugGenerator;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemTarget;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationSchemaTypeEnum;
use Capell\Navigation\Filament\Components\Forms\Navigation\TypeSelect;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Saade\FilamentAdjacencyList\Forms\Components\AdjacencyList;

class DefaultNavigationSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = NavigationSchemaTypeEnum::Navigation;

    /**
     * Array cache for loaded Page models by ID.
     *
     * @var array<int, Pageable|null>
     */
    private static array $pageCache = [];

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::Navigation->value);
    }

    public function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'replicate' => $this->getCreateOptionFormSchema($schema),
            'editOption' => $this->getEditOptionFormSchema($schema),
            default => $this->getFormSchema($schema),
        };
    }

    protected function getFormSchema(Schema $schema): array
    {
        return [
            FixedWidthSidebar::make()
                ->mainSchema($this->getMainFormSchema())
                ->sidebarSchema(
                    $this->getSettingsFormSchema($schema),
                    contained: true,
                ),
        ];
    }

    protected function getMainFormSchema(): array
    {
        return [
            Tabs::make()
                ->tabs([
                    Tab::make(__('capell-admin::form.items'))
                        ->icon(Heroicon::Bars3)
                        ->schema([
                            $this->getNavigationItemsField(),
                        ]),
                    Tab::make(__('capell-admin::form.settings'))
                        ->icon(Heroicon::OutlinedCog6Tooth)
                        ->statePath('meta')
                        ->columns()
                        ->schema([
                            TextInput::make('component')
                                ->label(__('capell-admin::form.component'))
                                ->helperText(__('capell-admin::generic.menu_component_info'))
                                ->placeholder('capell::list'),
                            TextInput::make('component_item')
                                ->label(__('capell-admin::form.component_item'))
                                ->helperText(__('capell-admin::generic.menu_component_item_info'))
                                ->placeholder('capell::list.item'),
                        ]),
                ]),
        ];
    }

    protected function getSettingsFormSchema(Schema $schema): array
    {
        return [
            NameInput::make('name')
                ->required()
                ->afterStateUpdatedJs(function (string $operation): string {
                    if (! in_array($operation, ['create', 'createOption', 'replicate'], true)) {
                        return '';
                    }

                    return SlugGenerator::slugifyState("\$state ?? ''", 'key');
                }),

            CustomSelectGroup::make(
                'key',
                NavigationHandle::class,
                modifySelectUsing: fn (Select $select): Select => $select->required()
                    ->unique(
                        column: 'key',
                        ignoreRecord: $schema->getOperation() !== 'replicate',
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->withoutTrashed()
                            ->where('site_id', $get('site_id'))
                            ->when(
                                $get('language_id', true),
                                fn (Unique $query, int $languageId): Unique => $query->where('language_id', $languageId),
                            ),
                    ),
            )
                ->label(__('capell-admin::table.key')),

            TypeSelect::make('type_id')
                ->live()
                ->withRelation()
                ->when(
                    $schema->isCreating(),
                    fn (TypeSelect $component): TypeSelect => $component->withCreateForm(),
                    fn (TypeSelect $component): TypeSelect => $component->withEditForm(),
                ),

            SiteSelect::make('site_id')
                ->required()
                ->reactive(),

            LanguageSelect::make('language_id')
                ->reactive()
                ->withRelationship()
                ->modifyRelationQueryUsing(
                    fn (Builder $query, Get $get): Builder => $query->when(
                        $get('site_id'),
                        fn (BuilderContract $query, int $siteId): Builder => $query->whereHas(
                            'sites',
                            fn (BuilderContract $query): BuilderContract => $query->where('sites.id', $siteId),
                        ),
                    ),
                ),

            PublishSection::make(),
        ];
    }

    protected function getNavigationItemsField(string $navigationFieldsKey = 'navigationTypeFields'): AdjacencyList
    {
        return AdjacencyList::make('items')
            ->label(__('capell-admin::form.navigation_items'))
            ->view('capell-admin::components.adjacency-list.builder')
            ->hiddenLabel()
            ->labelKey('label')
            ->columnSpanFull()
            ->childrenKey('children')
            ->itemLabel($this->getItemLabel(...))
            ->itemUrl($this->getItemUrl(...))
            ->addAction(
                fn (Action $action): Action => $action
                    ->label(__('capell-admin::button.add_navigation_item'))
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            )
            ->schema([
                Grid::make()
                    ->schema([
                        ToggleButtons::make('type')
                            ->label(__('capell-admin::form.type'))
                            ->required()
                            ->live()
                            ->inline()
                            ->options(NavigationItemType::class)
                            ->default(NavigationItemType::Page->value)
                            ->afterStateUpdated(
                                fn (ToggleButtons $component): Schema => $component
                                    ->getRootContainer()
                                    ->getComponent($navigationFieldsKey)
                                    ->getChildSchema()
                                    ->fill(),
                            ),
                        $this->getLabelField(),
                    ]),
                Grid::make()
                    ->key($navigationFieldsKey)
                    ->whenTruthy('type')
                    ->schema(fn (Get $get): array => $this->getNavigationItemFields($get('type'))),
            ]);
    }

    protected function getCreateOptionFormSchema(Schema $schema): array
    {
        return [
            Grid::make()
                ->gridContainer()
                ->columns(['default' => 1, '@lg' => 2])
                ->schema($this->getSettingsFormSchema($schema)),
            ...$this->getMainFormSchema(),
        ];
    }

    protected function getEditOptionFormSchema(Schema $schema): array
    {
        return [
            Grid::make()
                ->gridContainer()
                ->columns(['default' => 1, '@lg' => 2])
                ->schema($this->getSettingsFormSchema($schema)),
            ...$this->getSettingsFormSchema($schema),
        ];
    }

    protected function getNavigationItemFields(NavigationItemType $type): array
    {
        return match ($type) {
            NavigationItemType::Page => $this->getPageNavigationItemFields(),
            NavigationItemType::Link => $this->getLinkNavigationItemFields(),
        };
    }

    protected function getPageNavigationItemFields(): array
    {
        return [
            Grid::make()
                ->statePath('data')
                ->columnSpanFull()
                ->schema([
                    Group::make([
                        SiteSelect::make('site_id')
                            ->dehydrated(false)
                            ->afterStateUpdatedJs(<<<'JS'
                                $set('pageable_id', null);
                                $set('pageable_type', null);
                            JS),
                        Checkbox::make('auto_children')
                            ->label(__('capell-admin::form.auto_children'))
                            ->helperText(__('capell-admin::generic.auto_children_info'))
                            ->visible(fn (Get $get): bool => $get('pageable_type') === PageVariationEnum::Page->value),
                    ]),
                    PageMorphToOptionSelect::make()
                        ->whenTruthy('site_id')
                        ->modifyTypeSelectUsing(
                            fn (ToggleButtons $select): ToggleButtons => $select->default(PageVariationEnum::Page->value),
                        )
                        ->visibleJs(<<<'JS'
                            $get('site_id')
                        JS)
                        ->required(),
                    ...$this->getExtraItemFields(),
                ]),
        ];
    }

    protected function getLinkNavigationItemFields(): array
    {
        return [
            Grid::make()
                ->statePath('data')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('url')
                        ->label(__('capell-admin::form.url'))
                        ->validationAttribute(strtoupper(__('capell-admin::form.url')))
                        ->required()
                        ->columnSpanFull()
                        ->suffixAction(
                            fn (?string $state): ?Action => $state !== null
                                ? Action::make('open_url')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->url($state, true)
                                    ->openUrlInNewTab()
                                : null,
                        ),
                    ...$this->getExtraItemFields(),
                ]),
        ];
    }

    protected function getExtraItemFields(): array
    {
        return [
            Select::make('target')
                ->label(__('capell-admin::form.url_target'))
                ->options(NavigationItemTarget::class),
            Group::make()
                ->dense()
                ->schema([
                    IconPicker::make('icon')
                        ->label(__('capell-admin::form.icon'))
                        ->nullable(),
                    Checkbox::make('hide_label')
                        ->label(__('capell-admin::form.hide_label'))
                        ->hint(__('capell-admin::generic.hide_label_info'))
                        ->whenTruthy('icon')
                        ->visibleJs(<<<'JS'
                            return $get('icon') !== null;
                        JS),
                ]),
        ];
    }

    protected function getItemLabel(array $item, array &$pageCache = []): ?string
    {
        $navigationItem = NavigationItemData::from($item);

        if (filled($navigationItem->label)) {
            return $navigationItem->label;
        }

        if ($navigationItem->type === NavigationItemType::Page) {
            $page = $this->getCachedPageItem($navigationItem->data, $pageCache);

            if (! $page instanceof Pageable) {
                return null;
            }

            return $page->name;
        }

        return null;
    }

    protected function getItemUrl(array $item, Get $get, array &$pageCache = []): ?string
    {
        $navigationItem = NavigationItemData::from($item);

        $url = null;

        switch ($navigationItem->type) {
            case NavigationItemType::Page:
                $languageId = $get('language_id');
                $siteId = $get('site_id');

                $language = $this->getLanguageById($languageId, $siteId);

                $page = $this->getCachedPageItem($navigationItem->data, $pageCache, true, $language->id);

                if (! $page instanceof Pageable) {
                    return null;
                }

                $url = $page->pageUrl?->full_url;
                break;
            case NavigationItemType::Link:
                $url = $navigationItem->data['url'];
                break;
        }

        return $url;
    }

    protected function getLanguageById(?int $languageId, int $siteId): Language
    {
        return CapellCoreHelper::getLanguageByIdOrSite($languageId, $siteId);
    }

    /**
     * Get a Page model from cache, loading if necessary.
     *
     * @param  array<int, Pageable|null>  $pageCache
     */
    private function getCachedPageItem(array $data, array &$pageCache, bool $withUrl = false, ?int $languageId = null): ?Page
    {
        $pageId = $data['pageable_id'] ?? null;
        $pageType = $data['pageable_type'] ?? null;

        if ($pageId === null || $pageType === null) {
            return null;
        }

        if (isset($pageCache[$pageType][$pageId])) {
            return $pageCache[$pageType][$pageId];
        }

        /** @var class-string<Pageable&Model> $model */
        $model = Relation::getMorphedModel($pageType) ?? Page::class;

        if ($withUrl && $languageId !== null) {
            $page = $model::query()
                ->with([
                    'pageUrl.siteDomain' => fn (BuilderContract $query): BuilderContract => DB::getDriverName() === 'sqlite'
                        ? $query->orderByRaw('CASE WHEN language_id = ? THEN 0 ELSE 1 END', [$languageId])
                        : $query->orderByRaw('FIELD(language_id, ?) DESC', [$languageId]),
                ])
                ->find($pageId);
        } else {
            $page = $model::query()->find($pageId);
        }

        $pageCache[$pageType][$pageId] = $page;

        return $page;
    }

    private function getLabelField(): TextInput
    {
        return TextInput::make('label')
            ->label(__('capell-admin::form.label'))
            ->requiredIf('type', NavigationItemType::Link->value)
            ->helperText(
                fn (Get $get): ?string => $get('type') === NavigationItemType::Link->value
                    ? __('capell-admin::generic.navigation_page_label_info')
                    : null,
            );
    }
}
