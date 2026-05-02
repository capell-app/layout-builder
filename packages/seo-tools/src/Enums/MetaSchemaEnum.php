<?php

declare(strict_types=1);

namespace Capell\SeoTools\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;

enum MetaSchemaEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component('capell::schema.website')]
    case Website = 'website';

    #[Component('capell::schema.webpage')]
    case Webpage = 'webpage';

    #[Component('capell::schema.breadcrumb')]
    case Breadcrumb = 'breadcrumb';

    #[Component('capell::schema.image')]
    case Image = 'image';

    #[Component('capell::schema.organization')]
    case Organization = 'organization';

    #[Component('capell::schema.graph')]
    case Graph = 'graph';

    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }

    public function getComponent(): ?string
    {
        return $this->getCaseAttribute(Component::class)?->class;
    }
}
