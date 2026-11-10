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
        if (TableName::existsAndIsOwned('layout_presets', basename(__FILE__, '.php'))) {
            return;
        }

        Schema::create(TableName::resolve('layout_presets'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained(TableName::resolve('sites'))->cascadeOnDelete();
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
        Schema::dropIfExists(TableName::resolve('layout_presets'));
    }
};
