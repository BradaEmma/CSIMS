<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('roster_assignment_id')->nullable()->after('shift_instance_id');

            $table->foreign('roster_assignment_id')
                ->references('id')
                ->on('roster_assignments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['roster_assignment_id']);
            $table->dropColumn('roster_assignment_id');
        });
    }
};