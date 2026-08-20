<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->dropColumn([
                'attendance_date',
                'check_in_time',
                'check_out_time',
                'shift',
                'shift_type',
                'shift_date',
                'late_minutes',
            ]);

        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->date('attendance_date')->nullable();
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->string('shift')->nullable();
            $table->string('shift_type')->nullable();
            $table->date('shift_date')->nullable();
            $table->integer('late_minutes')->default(0);

        });
    }
};