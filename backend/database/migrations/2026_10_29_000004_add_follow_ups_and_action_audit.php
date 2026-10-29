<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('message');
            $table->timestamps();
        });
        Schema::table('consultation_events', function (Blueprint $table) {
            $table->foreignId('action_item_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultation_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('action_item_id');
        });
        Schema::dropIfExists('follow_ups');
    }
};
