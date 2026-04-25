<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Listeners;

use Capell\Core\Events\ThemeColorsUpdated;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegenerateTailwindAssetsOnThemeColorsUpdated
{
    public function handle(ThemeColorsUpdated $event): void
    {
        try {
            Artisan::call('capell:frontend-tailwind-assets', [
                '--theme-key' => $event->theme->key,
            ]);
        } catch (Throwable $throwable) {
            Log::warning('Failed to regenerate Tailwind assets after theme colors update.', [
                'theme_key' => $event->theme->key,
                'error' => $throwable->getMessage(),
            ]);
        }
    }
}
