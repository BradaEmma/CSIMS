<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guard_id')->constrained('guards')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('shift_date');
            $table->enum('shift_type', ['morning', 'night']);
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->integer('minutes_late')->default(0);
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'completed'])->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Core business rule: one record per guard per site per shift per date
            $table->unique(['guard_id', 'site_id', 'shift_date', 'shift_type'], 'unique_attendance');
            $table->index(['shift_date', 'shift_type']);
            $table->index(['guard_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};