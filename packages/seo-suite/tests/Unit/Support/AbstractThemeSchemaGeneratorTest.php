<?php

declare(strict_types=1);

use Capell\SeoSuite\Support\AbstractThemeSchemaGenerator;

it('hex escapes unsafe characters in JSON-LD output', function (): void {
    $generator = new class extends AbstractThemeSchemaGenerator
    {
        protected function resolveOrgName(): string
        {
            return 'Acme';
        }

        protected function resolveSameAs(): array
        {
            return [];
        }
    };

    $json = $generator->toJsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => '</script><script>alert("x")</script>',
        'description' => "Tom & O'Connor",
    ]);

    expect($json)
        ->toContain('\u003C/script\u003E')
        ->toContain('\u0026')
        ->toContain('\u0027')
        ->toContain('\u0022')
        ->not->toContain('</script>');
});
