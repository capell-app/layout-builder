<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\BuildLayoutContentInventoryAction;
use Capell\LayoutBuilder\Contracts\LayoutContentGroupContributor;
use Capell\LayoutBuilder\Data\LayoutContentGroupData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryContextData;
use Capell\LayoutBuilder\Data\LayoutContentInventoryData;
use Capell\LayoutBuilder\Data\LayoutContentItemData;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutBuilderEditorMode;
use Capell\LayoutBuilder\Filament\Extenders\Page\HeroPageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Schemas\Extenders\LayoutSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Pages\Schemas\Extenders\PageSchemaExtender;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminAliasRegistry;
use Capell\LayoutBuilder\Support\LayoutClipboard;
use Capell\LayoutBuilder\Support\LayoutPresetRepository;

it('keeps package namespace editor classes resolvable while admin namespaces remain compatible', function (): void {
    LayoutBuilderAdminAliasRegistry::register();

    foreach (LayoutBuilderAdminAliasRegistry::aliases() as $source => $alias) {
        expect(class_exists($alias) || enum_exists($alias))->toBeTrue()
            ->and(class_exists($source) || enum_exists($source))->toBeTrue();
    }
});

it('exposes package namespace aliases for admin surface classes during extraction', function (): void {
    LayoutBuilderAdminAliasRegistry::register();

    foreach ([
        ConfiguratorTypeEnum::class,
        HeroPageSchemaExtender::class,
        LayoutResource::class,
        LayoutSchemaExtender::class,
        PageSchemaExtender::class,
        LayoutClipboard::class,
        LayoutPresetRepository::class,
        WidgetResource::class,
    ] as $class) {
        expect(class_exists($class) || enum_exists($class))->toBeTrue();
    }
});

it('loads content inventory classes from the layout builder package instead of admin aliases', function (): void {
    foreach ([
        BuildLayoutContentInventoryAction::class,
        LayoutContentGroupContributor::class,
        LayoutContentGroupData::class,
        LayoutContentInventoryData::class,
        LayoutContentInventoryContextData::class,
        LayoutContentItemData::class,
        LayoutBreakpoint::class,
        LayoutBuilderEditorMode::class,
        LayoutClipboard::class,
        LayoutPresetRepository::class,
    ] as $class) {
        $reflection = new ReflectionClass($class);

        expect($reflection->getFileName())->toContain('packages/layout-builder/src');
    }
});
