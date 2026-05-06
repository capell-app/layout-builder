<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('search_console_url_metrics')) {
            return;
        }

        Schema::create('search_console_url_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->text('url');
            $table->string('url_hash', 64);
            $table->date('window_start');
            $table->date('window_end');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->double('ctr')->nullable();
            $table->double('average_position')->nullable();
            $table->unsignedInteger('previous_clicks')->default(0);
            $table->unsignedInteger('previous_impressions')->default(0);
            $table->double('previous_ctr')->nullable();
            $table->double('previous_average_position')->nullable();
            $table->integer('click_delta')->default(0);
            $table->integer('impression_delta')->default(0);
            $table->double('position_delta')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('site_id');
            $table->index(['window_start', 'window_end']);
            $table->index('click_delta');
            $table->unique(['site_id', 'url_hash', 'window_start', 'window_end'], 'search_console_url_metrics_unique_window');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_console_url_metrics');
    }
};
