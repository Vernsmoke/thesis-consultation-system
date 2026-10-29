<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('adviser_id')->constrained('users')->restrictOnDelete();
            $table->text('agenda');
            $table->enum('status', ['requested', 'confirmed', 'completed', 'missed'])->default('requested');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('schedule_version')->default(0);
            $table->text('meeting_notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'starts_at']);
            $table->index(['status', 'ends_at']);
        });

        Schema::create('consultation_panel', function (Blueprint $table) {
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->primary(['consultation_id', 'user_id']);
        });

        Schema::create('action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->foreignId('assigned_to_id')->constrained('users')->restrictOnDelete();
            $table->string('description', 500);
            $table->dateTime('due_at');
            $table->unsignedInteger('deadline_version')->default(1);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['completed_at', 'due_at']);
        });

        // Match the supported MariaDB/MySQL engine; tests use that engine too.
        DB::statement("ALTER TABLE consultations ADD CONSTRAINT consultations_schedule_check CHECK (
            (status = 'requested' AND starts_at IS NULL AND ends_at IS NULL AND schedule_version = 0)
            OR (status <> 'requested' AND starts_at IS NOT NULL AND ends_at IS NOT NULL
                AND ends_at > starts_at AND schedule_version >= 1)
        )");
        DB::statement('ALTER TABLE action_items ADD CONSTRAINT action_items_version_check CHECK (deadline_version >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('action_items');
        Schema::dropIfExists('consultation_panel');
        Schema::dropIfExists('consultations');
    }
};
