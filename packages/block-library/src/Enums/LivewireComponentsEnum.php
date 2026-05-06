<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use Capell\BlockLibrary\Support\LayoutBuilder\Livewire\ContentBlockAssets;
use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;

enum LivewireComponentsEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(ContentBlockAssets::class)]
    case ContentAssetsTable = 'capell-block-library::assets.table.content-block-assets';

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
