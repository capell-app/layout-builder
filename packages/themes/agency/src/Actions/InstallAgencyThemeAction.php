<?php

declare(strict_types=1);

namespace Capell\Themes\Agency\Actions;

use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs the Agency theme:
 *  - Ensures a row exists in `themes` with key=agency
 *  - Optionally seeds pre-built Mosaic layouts (home/work/contact)
 *
 * Plain invokable action — no hard dependency on lorisleiva/laravel-actions.
 */
class InstallAgencyThemeAction
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
            $layoutIds = (new SeedAgencyLayoutsAction)->handle();
        }

        return [
            'theme_id' => $themeId,
            'theme_key' => 'agency',
            'layouts' => $layoutIds,
        ];
    }

    /**
     * Insert or update the `themes` row for agency.
     */
    protected function upsertThemeRow(bool $force): ?int
    {
        $typeId = $this->resolveFrontendTypeId();

        $payload = [
            'name' => 'Agency',
            'type_id' => $typeId,
            'key' => 'agency',
            'meta' => json_encode([
                'version' => '1.0.0',
                'description' => 'Creative agency theme for Capell CMS.',
                'primary_color' => '#ff5a7e',
                'accent_color' => '#3b82f6',
            ]),
            'admin' => json_encode([
                'schema' => ThemeSettingsSchema::class,
            ]),
            'order' => 0,
            'default' => 0,
            'status' => 1,
            'updated_at' => now(),
        ];

        $existing = DB::table('themes')->where('key', 'agency')->first();

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
