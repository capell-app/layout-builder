<?php

declare(strict_types=1);

namespace Capell\Forms\Enums;

use Capell\Core\Enums\Attribute\Component;
use Capell\Core\Enums\Attribute\EnumAttributeHelper;
use Capell\Core\Enums\Attribute\EnumAttributeInterface;
use Capell\Forms\Livewire\FormComponent;

enum LivewireComponentEnum: string implements EnumAttributeInterface
{
    use EnumAttributeHelper;

    #[Component(FormComponent::class)]
    case Form = 'capell-forms::form';

    /**
     * @return array<string, class-string|null>
     */
    public static function getComponents(): array
    {
        $attributes = self::getAllCaseAttributes(Component::class);

        return array_map(fn (?Component $attribute): ?string => $attribute?->class ?? null, $attributes);
    }
}
