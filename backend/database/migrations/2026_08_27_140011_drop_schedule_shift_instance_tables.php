<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('schedule_assignments');
        Schema::dropIfExists('shift_instances');
        Schema::dropIfExists('schedules');
    }

    public function down(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->date('schedule_date');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->string('zone')->nullable();
            $table->enum('shift', ['morning', 'night']);
            $table->integer('required_guards')->default(1);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });

        Schema::create('shift_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->date('shift_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('status', ['open', 'closed', 'archived'])->default('open');
            $table->timestamps();
            $table->unique(['schedule_id', 'shift_date'], 'unique_shift_instance');
        });

        Schema::create('schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('shift_instance_id')->nullable()->constrained('shift_instances')->onDelete('set null');
            $table->foreignId('guard_id')->constrained('guards')->onDelete('cascade');
            $table->enum('status', ['planned', 'confirmed', 'replaced'])->default('planned');
            $table->timestamps();
        });
    }
};