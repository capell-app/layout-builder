<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('layout_presets')) {
            return;
        }

        Schema::create('layout_presets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('theme_key', 128)->nullable()->index();
            $table->string('name');
            $table->string('key', 128);
            $table->string('category', 128)->default('general')->index();
            $table->string('scope', 64)->default('layout_only')->index();
            $table->json('snapshot');
            $table->userstamps();
            $table->timestamps();
            $table->unique(['site_id', 'key']);
        });
    }

    public function down(): void
    {
        //
    }
};
