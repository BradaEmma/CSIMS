<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_instances', function (Blueprint $table) {
            $table->id();

            // links to your existing schedules table
            $table->foreignId('schedule_id')
                ->constrained('schedules')
                ->cascadeOnDelete();

            // the actual working day of the shift
            $table->date('shift_date');

            // real execution timestamps (IMPORTANT for night shift handling)
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            // operational state of the shift
            $table->enum('status', ['open', 'closed', 'archived'])
                ->default('open');

            $table->timestamps();

            // prevents duplicate shift instance per schedule per day
            $table->unique(['schedule_id', 'shift_date'], 'unique_shift_instance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_instances');
    }
};