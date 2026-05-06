<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\SeoSuite\Contracts\SchemaTemplate;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;
use Capell\SeoSuite\Support\SchemaTemplates\SchemaTemplateRegistry;

function schemaTemplateRegistryTestTemplate(): SchemaTemplate
{
    return new class implements SchemaTemplate
    {
        public function build(Page $page, Site $site, Language $language): array
        {
            return ['@type' => 'WebPage'];
        }

        public function requiredFields(Page $page, Site $site, Language $language): array
        {
            return ['@type'];
        }
    };
}

it('registers and retrieves schema templates by type', function (): void {
    $registry = new SchemaTemplateRegistry;
    $template = schemaTemplateRegistryTestTemplate();

    $registry->register(SchemaTemplateTypeEnum::WebPage, $template);

    expect($registry->get(SchemaTemplateTypeEnum::WebPage))->toBe($template)
        ->and($registry->all())->toHaveKey(SchemaTemplateTypeEnum::WebPage->value);
});

it('requires explicit replacement for duplicate registrations', function (): void {
    $registry = new SchemaTemplateRegistry;
    $originalTemplate = schemaTemplateRegistryTestTemplate();
    $replacementTemplate = schemaTemplateRegistryTestTemplate();

    $registry->register(SchemaTemplateTypeEnum::WebPage, $originalTemplate);

    expect(fn (): null => $registry->register(SchemaTemplateTypeEnum::WebPage, $replacementTemplate))
        ->toThrow(InvalidArgumentException::class);

    $registry->replace(SchemaTemplateTypeEnum::WebPage, $replacementTemplate);

    expect($registry->get(SchemaTemplateTypeEnum::WebPage))->toBe($replacementTemplate);
});

it('can register fallback templates without overwriting extensions', function (): void {
    $registry = new SchemaTemplateRegistry;
    $extensionTemplate = schemaTemplateRegistryTestTemplate();
    $fallbackTemplate = schemaTemplateRegistryTestTemplate();

    $registry->replace(SchemaTemplateTypeEnum::WebPage, $extensionTemplate);
    $registry->registerIfMissing(SchemaTemplateTypeEnum::WebPage, $fallbackTemplate);

    expect($registry->get(SchemaTemplateTypeEnum::WebPage))->toBe($extensionTemplate);
});

it('lists templates matching the page schema type', function (): void {
    $registry = new SchemaTemplateRegistry;
    $webPageTemplate = schemaTemplateRegistryTestTemplate();
    $articleTemplate = schemaTemplateRegistryTestTemplate();
    $page = new Page;
    $type = new Type;
    $type->meta = ['schema' => ['type' => 'BlogPosting']];

    $page->setRelation('type', $type);

    $registry->register(SchemaTemplateTypeEnum::WebPage, $webPageTemplate);
    $registry->register(SchemaTemplateTypeEnum::Article, $articleTemplate);

    expect($registry->matching($page))
        ->toHaveKey(SchemaTemplateTypeEnum::Article->value)
        ->not()->toHaveKey(SchemaTemplateTypeEnum::WebPage->value);
});

it('does not treat the default WebPage fallback as an explicit requirement', function (): void {
    $registry = new SchemaTemplateRegistry;
    $page = new Page;

    expect($registry->pageRequires($page, SchemaTemplateTypeEnum::WebPage))->toBeFalse();
});
