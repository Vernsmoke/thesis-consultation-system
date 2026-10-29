<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_items', function (Blueprint $table) {
            $table->id();
            $table->char('deduplication_key', 64)->unique();
            $table->uuid('correlation_id')->unique();
            $table->enum('kind', ['reminder', 'missed_session', 'action_deadline', 'progress_summary']);
            $table->foreignId('consultation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('action_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('review_owner_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('source_version')->default(0);
            $table->dateTime('scheduled_for');
            $table->json('payload');
            // Delivery states are reserved for the next Week 13 worker increment.
            $table->enum('status', ['pending', 'claimed', 'retry_wait', 'delivered', 'needs_review', 'cancelled'])->default('pending');
            $table->string('failure_code', 80)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('available_at');
            $table->dateTime('claimed_until')->nullable();
            $table->string('receipt', 100)->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
        });
        Schema::table('consultation_events', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->change();
            $table->enum('actor_type', ['user', 'automation'])->default('user');
        });
    }

    public function down(): void
    {
        if (DB::table('consultation_events')->where('actor_type', 'automation')->exists()) {
            throw new RuntimeException('Keep automated audit history; review rollback manually instead of relabeling actors.');
        }
        Schema::dropIfExists('automation_items');
        Schema::table('consultation_events', function (Blueprint $table) {
            $table->dropColumn('actor_type');
            $table->foreignId('actor_id')->nullable(false)->change();
        });
    }
};
