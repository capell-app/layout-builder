<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\SchemaTemplates;

use Capell\Core\Models\Page;
use Capell\SeoTools\Contracts\SchemaTemplate;
use Capell\SeoTools\Enums\SchemaTemplateTypeEnum;
use InvalidArgumentException;

class SchemaTemplateRegistry
{
    /**
     * @var array<string, SchemaTemplate>
     */
    private array $templates = [];

    public function register(SchemaTemplateTypeEnum $type, SchemaTemplate $template): void
    {
        if (isset($this->templates[$type->value])) {
            throw new InvalidArgumentException(sprintf('Schema template [%s] is already registered.', $type->value));
        }

        $this->templates[$type->value] = $template;
    }

    public function replace(SchemaTemplateTypeEnum $type, SchemaTemplate $template): void
    {
        $this->templates[$type->value] = $template;
    }

    public function registerIfMissing(SchemaTemplateTypeEnum $type, SchemaTemplate $template): void
    {
        if ($this->get($type) instanceof SchemaTemplate) {
            return;
        }

        $this->templates[$type->value] = $template;
    }

    public function get(SchemaTemplateTypeEnum $type): ?SchemaTemplate
    {
        return $this->templates[$type->value] ?? null;
    }

    /**
     * @return array<string, SchemaTemplate>
     */
    public function all(): array
    {
        return $this->templates;
    }

    /**
     * @return array<string, SchemaTemplate>
     */
    public function matching(Page $page): array
    {
        $schemaType = $this->pageSchemaType($page);

        return array_filter(
            $this->templates,
            static fn (SchemaTemplate $template, string $type): bool => SchemaTemplateTypeEnum::from($type)->matchesSchemaType($schemaType),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function pageRequires(Page $page, SchemaTemplateTypeEnum $type): bool
    {
        $schemaType = $this->pageSchemaType($page);

        if ($schemaType === null || $schemaType === '') {
            return false;
        }

        return $type->matchesSchemaType($schemaType);
    }

    private function pageSchemaType(Page $page): ?string
    {
        /** @var string|null $schemaType */
        $schemaType = data_get($page, 'type.meta.schema.type');

        return $schemaType;
    }
}
