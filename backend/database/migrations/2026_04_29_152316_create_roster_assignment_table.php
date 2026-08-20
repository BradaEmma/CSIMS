<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->string('shift'); // morning / night

            $table->boolean('is_overtime')->default(false);
            $table->boolean('generated_by_system')->default(true);

            $table->timestamps();

            // Prevent duplicate per shift (correct level)
            $table->unique(['guard_id', 'date', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_assignments');
    }
};