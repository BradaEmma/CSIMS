<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->date('attendance_date')->nullable()->change();
            $table->timestamp('check_in_time')->nullable()->change();
            $table->timestamp('check_out_time')->nullable()->change();
        });
    }

    public function down(): void {}
};