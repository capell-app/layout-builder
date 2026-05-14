<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\LayoutBuilderAdminAliasRegistry;

it('exposes current admin editor classes through package namespaces', function (): void {
    LayoutBuilderAdminAliasRegistry::register();

    foreach (LayoutBuilderAdminAliasRegistry::aliases() as $source => $alias) {
        expect(class_exists($alias) || enum_exists($alias))->toBeTrue()
            ->and(is_a($alias, $source, true))->toBeTrue();
    }
});
