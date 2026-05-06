<?php

declare(strict_types=1);

use Capell\Insights\Actions\ImportLegacyPageViewsAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_views')) {
            return;
        }

        ImportLegacyPageViewsAction::run();
    }

    public function down(): void
    {
        //
    }
};
