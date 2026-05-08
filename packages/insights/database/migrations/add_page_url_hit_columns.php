<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_urls')) {
            return;
        }

        Schema::table('page_urls', function (Blueprint $table): void {
            if (! Schema::hasColumn('page_urls', 'hit_count')) {
                $table->unsignedInteger('hit_count')->default(0)->after('is_manual');
            }

            if (! Schema::hasColumn('page_urls', 'last_hit_at')) {
                $table->timestamp('last_hit_at')->nullable()->after('hit_count');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('page_urls')) {
            return;
        }

        Schema::table('page_urls', function (Blueprint $table): void {
            if (Schema::hasColumn('page_urls', 'last_hit_at')) {
                $table->dropColumn('last_hit_at');
            }

            if (Schema::hasColumn('page_urls', 'hit_count')) {
                $table->dropColumn('hit_count');
            }
        });
    }
};
