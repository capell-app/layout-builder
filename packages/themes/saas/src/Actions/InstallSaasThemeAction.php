<?php

declare(strict_types=1);

namespace Capell\Themes\Saas\Actions;

use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs the SaaS theme:
 *  - Ensures a row exists in `themes` with key=saas
 *  - Optionally seeds pre-built Mosaic layouts (home/pricing/features)
 *
 * Plain invokable action (no hard dependency on lorisleiva/laravel-actions).
 */
class InstallSaasThemeAction
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
            $layoutIds = (new SeedSaasLayoutsAction)->handle();
        }

        return [
            'theme_id' => $themeId,
            'theme_key' => 'saas',
            'layouts' => $layoutIds,
        ];
    }

    /**
     * Insert or update the `themes` row for saas.
     */
    protected function upsertThemeRow(bool $force): ?int
    {
        $typeId = $this->resolveFrontendTypeId();

        $payload = [
            'name' => 'SaaS',
            'type_id' => $typeId,
            'key' => 'saas',
            'meta' => json_encode([
                'version' => '1.0.0',
                'description' => 'Modern, conversion-optimized SaaS theme for Capell CMS.',
                'primary_color' => '#6366f1',
                'accent_color' => '#10b981',
            ]),
            'admin' => json_encode([
                'schema' => ThemeSettingsSchema::class,
            ]),
            'order' => 0,
            'default' => 0,
            'status' => 1,
            'updated_at' => now(),
        ];

        $existing = DB::table('themes')->where('key', 'saas')->first();

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
