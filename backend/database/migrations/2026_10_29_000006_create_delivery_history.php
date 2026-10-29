<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('cycle')->default(1);
            $table->unsignedSmallInteger('total_attempts')->default(0);
            $table->uuid('claim_token')->nullable();
        });
        Schema::create('automation_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('cycle');
            $table->unsignedSmallInteger('number');
            $table->uuid('claim_token')->unique();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->string('outcome', 30)->default('started');
            $table->string('code', 80)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('receipt', 100)->nullable();
            $table->unique(['automation_item_id', 'cycle', 'number']);
        });
        Schema::create('automation_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedSmallInteger('cycle');
            $table->string('reason', 500);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        if (DB::table('automation_attempts')->exists() || DB::table('automation_recoveries')->exists()) {
            throw new RuntimeException('Delivery history exists; review rollback without deleting evidence.');
        }
        Schema::dropIfExists('automation_recoveries');
        Schema::dropIfExists('automation_attempts');
        Schema::table('automation_items', function (Blueprint $table) {
            $table->dropColumn(['cycle', 'total_attempts', 'claim_token']);
        });
    }
};
