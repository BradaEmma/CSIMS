<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_assignments', function (Blueprint $table) {

    // ONLY add foreign key if it's missing
    if (!Schema::hasColumn('schedule_assignments', 'shift_instance_id')) {
        $table->foreignId('shift_instance_id')
            ->nullable()
            ->after('schedule_id');
    }
});
    }

    public function down(): void
    {
        Schema::table('schedule_assignments', function (Blueprint $table) {
            $table->dropForeign(['shift_instance_id']);
            $table->dropColumn('shift_instance_id');
        });
    }
};