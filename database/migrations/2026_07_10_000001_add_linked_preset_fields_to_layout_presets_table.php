<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable(TableName::resolve('layout_presets'))) {
            return;
        }

        Schema::table(TableName::resolve('layout_presets'), function (Blueprint $table): void {
            if (! Schema::hasColumn(TableName::resolve('layout_presets'), 'mode')) {
                $table->string('mode', 32)->default('copy')->index()->after('scope');
            }

            if (! Schema::hasColumn(TableName::resolve('layout_presets'), 'snapshot_version')) {
                $table->unsignedInteger('snapshot_version')->default(1)->after('mode');
            }

            if (! Schema::hasColumn(TableName::resolve('layout_presets'), 'revision')) {
                $table->unsignedInteger('revision')->default(1)->after('snapshot_version');
            }

            if (! Schema::hasColumn(TableName::resolve('layout_presets'), 'tags')) {
                $table->json('tags')->nullable()->after('category');
            }

            if (! Schema::hasColumn(TableName::resolve('layout_presets'), 'description')) {
                $table->string('description')->nullable()->after('tags');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(TableName::resolve('layout_presets'))) {
            return;
        }

        Schema::table(TableName::resolve('layout_presets'), function (Blueprint $table): void {
            foreach (['description', 'tags', 'revision', 'snapshot_version', 'mode'] as $column) {
                if (Schema::hasColumn(TableName::resolve('layout_presets'), $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
