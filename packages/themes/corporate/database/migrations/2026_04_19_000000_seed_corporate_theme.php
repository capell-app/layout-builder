<?php

declare(strict_types=1);

use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Idempotent: will not create a duplicate row.
     */
    public function up(): void
    {
        if (! Schema::hasTable('themes')) {
            // Capell core migrations haven't run yet; skip — another host
            // app that doesn't have the themes table simply gets a no-op.
            return;
        }

        if (DB::table('themes')->where('key', 'corporate')->exists()) {
            return;
        }

        $typeId = $this->resolveFrontendTypeId();

        DB::table('themes')->insert([
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('themes')) {
            return;
        }

        DB::table('themes')->where('key', 'corporate')->delete();
    }

    /**
     * Query theme_types for the 'frontend' type, fall back to 1.
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
};
