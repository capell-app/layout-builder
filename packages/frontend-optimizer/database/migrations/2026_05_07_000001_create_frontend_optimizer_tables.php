<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frontend_render_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->string('scope');
            $table->string('label')->nullable();
            $table->json('signature');
            $table->json('manifest')->nullable();
            $table->string('critical_css_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('frontend_optimization_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('render_profile_id')->constrained('frontend_render_profiles')->cascadeOnDelete();
            $table->string('status')->index();
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frontend_optimization_runs');
        Schema::dropIfExists('frontend_render_profiles');
    }
};
