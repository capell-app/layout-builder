<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResetLayoutContainerThemeSettingsAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function handle(array $meta, string $themeKey): array
    {
        $themeSettings = is_array($meta['theme_settings'] ?? null) ? $meta['theme_settings'] : [];

        unset($themeSettings[$themeKey]);

        if ($themeSettings === []) {
            unset($meta['theme_settings']);
        } else {
            $meta['theme_settings'] = $themeSettings;
        }

        return $meta;
    }
}
