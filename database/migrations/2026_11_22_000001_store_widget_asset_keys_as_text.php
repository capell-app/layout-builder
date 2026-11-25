<?php

declare(strict_types=1);

use Capell\Core\Support\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A widget asset's asset_id holds the key of any model, most of which are numeric. Laravel's
 * mariadb driver creates uuid columns as MariaDB's native UUID type, which refuses a numeric key
 * and cannot be compared with a numeric column, so every page that sorts widget assets by title
 * failed. Store the key as the CHAR(36) the mysql driver has always created. Other drivers already
 * hold text and are left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! TableName::existsAndIsOwned('widget_assets', '2026_05_10_190841_03_create_widget_assets_table')) {
            return;
        }

        $table = TableName::resolve('widget_assets');

        if (Schema::getColumnType($table, 'asset_id') !== 'uuid') {
            return;
        }

        Schema::table($table, static function (Blueprint $blueprint): void {
            $blueprint->char('asset_id', 36)->change();
        });
    }

    public function down(): void
    {
        // Deliberately not reversed: a native UUID column would reject the numeric keys stored since.
    }
};
