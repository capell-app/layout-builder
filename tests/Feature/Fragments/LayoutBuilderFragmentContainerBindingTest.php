<?php

declare(strict_types=1);

use Capell\Frontend\Contracts\Fragments\PublicFragmentUrlResolver;
use Capell\Frontend\Support\Fragments\PublicFragmentUrlResolverRegistry;
use Capell\LayoutBuilder\Fragments\LayoutBuilderFragmentUrlResolver;

it('registers the layout builder fragment owner through the real container', function (): void {
    $registry = resolve(PublicFragmentUrlResolverRegistry::class);
    $tagged = collect(app()->tagged(PublicFragmentUrlResolver::TAG));

    expect($registry->has(LayoutBuilderFragmentUrlResolver::OWNER))->toBeTrue()
        ->and($tagged->filter(
            fn (object $resolver): bool => $resolver instanceof LayoutBuilderFragmentUrlResolver,
        ))->toHaveCount(1);
});
