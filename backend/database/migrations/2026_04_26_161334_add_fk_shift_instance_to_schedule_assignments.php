<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_assignments', function (Blueprint $table) {

            // Add missing foreign key constraint only
            $table->foreign('shift_instance_id')
                ->references('id')
                ->on('shift_instances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_assignments', function (Blueprint $table) {
            $table->dropForeign(['shift_instance_id']);
        });
    }
};
