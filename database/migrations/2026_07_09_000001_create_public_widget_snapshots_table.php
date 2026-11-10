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
        if (TableName::existsAndIsOwned('public_widget_snapshots', basename(__FILE__, '.php'))) {
            return;
        }

        Schema::create(TableName::resolve('public_widget_snapshots'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained(TableName::resolve('sites'))->cascadeOnDelete();
            $table->string('pageable_type');
            $table->unsignedBigInteger('pageable_id');
            $table->foreignId('language_id')->constrained(TableName::resolve('languages'))->cascadeOnDelete();
            $table->unsignedBigInteger('layout_id')->nullable();
            $table->unsignedBigInteger('theme_id')->nullable();
            $table->string('render_profile')->default('blade');
            $table->string('owner_revision', 64);
            $table->string('context_fingerprint', 64);
            $table->string('current_key', 64)->nullable()->unique();
            $table->string('target_instance_id', 100);
            $table->string('widget_key', 150);
            $table->unsignedInteger('definition_state_version');
            $table->longText('encrypted_payload');
            $table->timestamp('superseded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['pageable_type', 'pageable_id', 'language_id'], TableName::namedIndex('public_widget_snapshots', 'widget_snapshots_page_language'));
            $table->index(['site_id', 'target_instance_id', 'revoked_at'], TableName::namedIndex('public_widget_snapshots', 'widget_snapshots_active_target'));
            $table->index(['superseded_at', 'expires_at'], TableName::namedIndex('public_widget_snapshots', 'widget_snapshots_retention'));
            $table->index(['context_fingerprint', 'target_instance_id'], TableName::namedIndex('public_widget_snapshots', 'widget_snapshots_context_target'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::resolve('public_widget_snapshots'));
    }
};
