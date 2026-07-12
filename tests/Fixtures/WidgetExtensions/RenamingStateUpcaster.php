<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;

final class RenamingStateUpcaster implements WidgetExtensionStateUpcaster
{
    public function upcast(array $state, int $fromVersion, int $toVersion): array
    {
        if ($fromVersion === 1 && $toVersion === 2 && array_key_exists('old_title', $state)) {
            $state['title'] = $state['old_title'];
            unset($state['old_title']);
        }

        return $state;
    }
}
