<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('assignments_created')->default(0);
            $table->integer('shortages_count')->default(0);
            $table->json('shortages_detail')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_generation_logs');
    }
};