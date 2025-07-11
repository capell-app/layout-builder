<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained();
            $table->string('key', 128);
            $table->publishDates();
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->json('admin')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('status')->index()->default(1);
            $table->userstamps();
            $table->timestamps();
            $table->unique(['key', 'deleted_at']);
            $table->softDeletes();
        });
    }
};
