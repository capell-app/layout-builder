<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Seed a single "Version 1" row representing the live state of every
     * record that exists at the moment this migration runs. Its `manifest`
     * is empty — the publisher fills manifests for future versions, and the
     * bootstrap row only needs to exist so that new publishing-studio have a
     * `base_version_id` to point at.
     */
    public function up(): void
    {
        if (! Schema::hasTable('versions')) {
            return;
        }

        if (DB::table('versions')->where('is_live', true)->exists()) {
            return;
        }

        $now = now();

        DB::table('versions')->insert([
            'uuid' => (string) Str::uuid(),
            'number' => 1,
            'name' => 'Bootstrap',
            'notes' => 'Automatically created as the baseline live version when publishing-studio were introduced.',
            'is_live' => true,
            'manifest' => json_encode((object) []),
            'source_workspace_id' => null,
            'published_by_type' => null,
            'published_by_id' => null,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('versions')) {
            return;
        }

        DB::table('versions')
            ->where('name', 'Bootstrap')
            ->where('number', 1)
            ->delete();
    }
};
