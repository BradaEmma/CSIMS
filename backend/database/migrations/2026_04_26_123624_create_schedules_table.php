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
        Schema::create('schedules', function (Blueprint $table) {
    $table->id();
    $table->date('schedule_date');
    $table->foreignId('site_id')->constrained()->cascadeOnDelete();
    $table->string('zone')->nullable();
    $table->enum('shift', ['morning', 'night']);
    $table->integer('required_guards')->default(1);
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
