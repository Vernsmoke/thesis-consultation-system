<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->uuid('correlation_id')->unique();
            $table->string('event', 50);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->unsignedInteger('schedule_version');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_events');
    }
};
