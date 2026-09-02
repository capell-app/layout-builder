<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `widgets` already carries `workspace_id` from its own creation migration,
 * but Publishing Studio's generic core-tables migration
 * (`2026_05_10_190866_08_z_add_workspace_columns_to_core_tables.php`) never
 * targeted it, so `shadowed_by_workspace_id` was missing.
 *
 * Widget is registered as a page type via
 * `LayoutBuilderServiceProvider::registerPageTypes()` ->
 * `PackageSurfaceRegistrar::blueprintSubject()`, which flows into
 * `PublishingStudioServiceProvider::registerPageTypeDraftables()` and
 * `applyBehaviorToDraftableModels()`. That attaches
 * `WorkspaceContextScope` to every `Widget` query, and the scope requires
 * both workspace columns to be present together — it throws rather than
 * silently degrading isolation. `LayoutBuilderActionFactory` also already
 * writes `shadowed_by_workspace_id` when restoring a live draftable asset
 * snapshot, so the column was assumed to exist without ever being added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('widgets')) {
            return;
        }

        Schema::table('widgets', function (Blueprint $table): void {
            if (! Schema::hasColumn('widgets', 'shadowed_by_workspace_id')) {
                $table->unsignedBigInteger('shadowed_by_workspace_id')->default(0)->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('widgets')) {
            return;
        }

        Schema::table('widgets', function (Blueprint $table): void {
            if (Schema::hasColumn('widgets', 'shadowed_by_workspace_id')) {
                $table->dropIndex(['shadowed_by_workspace_id']);
                $table->dropColumn('shadowed_by_workspace_id');
            }
        });
    }
};
