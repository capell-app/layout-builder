<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_seo_snapshots') || ! Schema::hasColumn('page_seo_snapshots', 'redirect_opportunities_count')) {
            return;
        }

        Schema::table('page_seo_snapshots', function (Blueprint $table): void {
            $table->dropColumn('redirect_opportunities_count');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('page_seo_snapshots') || Schema::hasColumn('page_seo_snapshots', 'redirect_opportunities_count')) {
            return;
        }

        Schema::table('page_seo_snapshots', function (Blueprint $table): void {
            $table->unsignedSmallInteger('redirect_opportunities_count')->default(0)->after('internal_link_suggestions_count');
        });
    }
};
