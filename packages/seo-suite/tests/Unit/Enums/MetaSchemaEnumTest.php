<?php

declare(strict_types=1);

use Capell\SeoSuite\Enums\MetaSchemaEnum;

describe('MetaSchemaEnum', function (): void {
    it('returns correct component for each case', function (): void {
        expect(MetaSchemaEnum::Website->getComponent())->toBe('capell::schema.website')
            ->and(MetaSchemaEnum::Webpage->getComponent())->toBe('capell::schema.webpage')
            ->and(MetaSchemaEnum::Breadcrumb->getComponent())->toBe('capell::schema.breadcrumb')
            ->and(MetaSchemaEnum::Image->getComponent())->toBe('capell::schema.image')
            ->and(MetaSchemaEnum::Organization->getComponent())->toBe('capell::schema.organization')
            ->and(MetaSchemaEnum::Graph->getComponent())->toBe('capell::schema.graph');
    });

    it('returns all components as key => component pairs', function (): void {
        $components = MetaSchemaEnum::getComponents();
        expect($components)->toBe([
            'website' => 'capell::schema.website',
            'webpage' => 'capell::schema.webpage',
            'breadcrumb' => 'capell::schema.breadcrumb',
            'image' => 'capell::schema.image',
            'organization' => 'capell::schema.organization',
            'graph' => 'capell::schema.graph',
        ]);
    });

    it('returns correct key for each case', function (): void {
        expect(MetaSchemaEnum::Website->value)->toBe('website')
            ->and(MetaSchemaEnum::Webpage->value)->toBe('webpage')
            ->and(MetaSchemaEnum::Breadcrumb->value)->toBe('breadcrumb')
            ->and(MetaSchemaEnum::Image->value)->toBe('image')
            ->and(MetaSchemaEnum::Organization->value)->toBe('organization')
            ->and(MetaSchemaEnum::Graph->value)->toBe('graph');
    });
});
