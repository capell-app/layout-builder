<?php

declare(strict_types=1);

use Capell\Notes\Enums\NoteReminderRecurrence;
use Capell\Notes\Enums\NoteStatus;
use Capell\Notes\Enums\NoteVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notes')) {
            Schema::create('notes', function (Blueprint $table): void {
                $table->id();
                $table->nullableMorphs('subject');
                $table->nullableMorphs('author');
                $table->text('body');
                $table->string('status')->default(NoteStatus::Open->value)->index();
                $table->string('visibility')->default(NoteVisibility::RecordEditors->value)->index();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['subject_type', 'subject_id', 'status'], 'notes_subject_status_index');
            });
        }

        if (! Schema::hasTable('note_assignments')) {
            Schema::create('note_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
                $table->morphs('assignee');
                $table->nullableMorphs('assigned_by');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['note_id', 'assignee_type', 'assignee_id'], 'note_assignment_unique');
                $table->index(['assignee_type', 'assignee_id', 'completed_at'], 'note_assignment_user_index');
            });
        }

        if (! Schema::hasTable('note_mentions')) {
            Schema::create('note_mentions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
                $table->morphs('mentioned');
                $table->nullableMorphs('mentioned_by');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->unique(['note_id', 'mentioned_type', 'mentioned_id'], 'note_mention_unique');
                $table->index(['mentioned_type', 'mentioned_id', 'read_at'], 'note_mention_user_index');
            });
        }

        if (! Schema::hasTable('note_reminders')) {
            Schema::create('note_reminders', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
                $table->timestamp('due_at')->nullable()->index();
                $table->string('timezone')->default('UTC');
                $table->string('recurrence')->default(NoteReminderRecurrence::None->value)->index();
                $table->timestamp('next_due_at')->nullable()->index();
                $table->timestamp('last_notified_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->unique('note_id', 'note_reminder_note_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('note_reminders');
        Schema::dropIfExists('note_mentions');
        Schema::dropIfExists('note_assignments');
        Schema::dropIfExists('notes');
    }
};
