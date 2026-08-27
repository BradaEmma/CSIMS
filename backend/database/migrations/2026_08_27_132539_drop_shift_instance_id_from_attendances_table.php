<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['shift_instance_id']);
            $table->dropColumn('shift_instance_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('shift_instance_id')
                ->nullable()
                ->after('site_id')
                ->constrained('shift_instances')
                ->nullOnDelete();
        });
    }
};