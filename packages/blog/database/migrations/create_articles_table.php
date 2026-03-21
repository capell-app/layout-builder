<?php

declare(strict_types=1);

use Capell\Core\Database\Concerns\CreatesDraftsSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use CreatesDraftsSchema;

    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained();
            $table->foreignId('layout_id')->constrained();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->visibleDates();
            $this->draftsCreateSchema($table);
            $table->unsignedInteger('order')->default(0);
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'type_id']);
            $table->index(['site_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
