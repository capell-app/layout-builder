<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->timestamp('unpublish_at')->nullable()->after('publish_at')->index();
            $table->timestamp('embargo_until')->nullable()->after('unpublish_at')->index();
            $table->timestamp('review_reminder_at')->nullable()->after('embargo_until')->index();
            $table->index(['status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropIndex(['status', 'publish_at']);
            $table->dropColumn([
                'unpublish_at',
                'embargo_until',
                'review_reminder_at',
            ]);
        });
    }
};
