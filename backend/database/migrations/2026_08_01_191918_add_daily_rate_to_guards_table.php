<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->decimal('daily_rate', 12, 2)->nullable()->after('shift_type');
        });
    }

    public function down(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->dropColumn('daily_rate');
        });
    }
};