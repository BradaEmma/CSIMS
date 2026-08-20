<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guard_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guard_id')
                ->constrained('guards')
                ->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained('sites')
                ->cascadeOnDelete();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->enum('status', ['active', 'ended'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_assignments');
    }
};