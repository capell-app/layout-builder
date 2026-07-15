<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Enums\WidgetSpacingValue;
use Lorisleiva\Actions\Concerns\AsObject;

final class NormalizeLayoutContainerPaddingAction
{
    use AsObject;

    private const array VERTICAL_VALUES = ['sm', 'md', 'lg', 'xl'];

    /**
     * @return list<string>|null
     */
    public function handle(mixed $state): ?array
    {
        if (is_string($state)) {
            $state = [$state];
        }

        if (! is_array($state)) {
            return null;
        }

        $values = array_values(array_unique(array_filter(
            $state,
            static fn (mixed $value): bool => is_string($value)
                && WidgetSpacingValue::tryFrom($value) instanceof WidgetSpacingValue,
        )));

        if ($values === []) {
            return null;
        }

        if (in_array(WidgetSpacingValue::None->value, $values, true)) {
            return [WidgetSpacingValue::None->value];
        }

        $verticalValues = array_values(array_filter(
            $values,
            static fn (string $value): bool => in_array($value, self::VERTICAL_VALUES, true),
        ));

        if ($verticalValues !== []) {
            return [end($verticalValues)];
        }

        $top = array_values(array_filter(
            $values,
            static fn (string $value): bool => str_starts_with($value, 't-'),
        ));
        $bottom = array_values(array_filter(
            $values,
            static fn (string $value): bool => str_starts_with($value, 'b-'),
        ));

        return array_values(array_filter([
            $top === [] ? null : end($top),
            $bottom === [] ? null : end($bottom),
        ]));
    }
}
