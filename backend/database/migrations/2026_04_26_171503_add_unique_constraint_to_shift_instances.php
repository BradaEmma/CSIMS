<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_instances', function (Blueprint $table) {
            // Enforce: only one shift instance per schedule per day
            $table->unique(
                ['schedule_id', 'shift_date'],
                'shift_instances_schedule_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('shift_instances', function (Blueprint $table) {
            $table->dropUnique('shift_instances_schedule_date_unique');
        });
    }
};