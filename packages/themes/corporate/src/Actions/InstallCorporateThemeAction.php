<?php

declare(strict_types=1);

namespace Capell\Themes\Corporate\Actions;

use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs the Corporate theme:
 *  - Ensures a row exists in `themes` with key=corporate
 *  - Optionally seeds pre-built Mosaic layouts (home/about/contact)
 *
 * If lorisleiva/laravel-actions is installed we could declare the Actions
 * traits — we avoid a hard dependency and provide a plain invokable instead.
 */
class InstallCorporateThemeAction
{
    /**
     * @param  array{force?: bool, seed_layouts?: bool}  $options
     * @return array{theme_id: ?int, theme_key: string, layouts: array<int, int>}
     */
    public function handle(array $options = []): array
    {
        $force = $options['force'] ?? false;
        $seedLayouts = $options['seed_layouts'] ?? false;

        $themeId = null;

        if (Schema::hasTable('themes')) {
            $themeId = $this->upsertThemeRow($force);
        }

        $layoutIds = [];
        if ($seedLayouts) {
            $layoutIds = (new SeedCorporateLayoutsAction)->handle();
        }

        return [
            'theme_id' => $themeId,
            'theme_key' => 'corporate',
            'layouts' => $layoutIds,
        ];
    }

    /**
     * Insert or update the `themes` row for corporate.
     */
    protected function upsertThemeRow(bool $force): ?int
    {
        $typeId = $this->resolveFrontendTypeId();

        $payload = [
            'name' => 'Corporate',
            'type_id' => $typeId,
            'key' => 'corporate',
            'meta' => json_encode([
                'version' => '1.0.0',
                'description' => 'Professional corporate theme for Capell CMS.',
                'primary_color' => '#1a2d6d',
                'accent_color' => '#f59e0b',
            ]),
            'admin' => json_encode([
                'schema' => ThemeSettingsSchema::class,
            ]),
            'order' => 0,
            'default' => 0,
            'status' => 1,
            'updated_at' => now(),
        ];

        $existing = DB::table('themes')->where('key', 'corporate')->first();

        if ($existing !== null) {
            if ($force) {
                DB::table('themes')->where('id', $existing->id)->update($payload);
            }

            return (int) $existing->id;
        }

        $payload['created_at'] = now();
        $id = DB::table('themes')->insertGetId($payload);

        return $id;
    }

    /**
     * Resolve the frontend theme type id, falling back to 1 if the table
     * doesn't exist or no match is found.
     */
    protected function resolveFrontendTypeId(): int
    {
        if (! Schema::hasTable('theme_types')) {
            return 1;
        }

        $row = DB::table('theme_types')
            ->where(function ($q): void {
                $q->where('key', 'frontend')->orWhere('name', 'frontend');
            })
            ->first();

        return $row !== null ? (int) $row->id : 1;
    }
}
