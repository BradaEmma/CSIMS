<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->boolean('nssf_applicable')->default(false)->after('daily_rate');
            $table->boolean('paye_applicable')->default(false)->after('nssf_applicable');
        });
    }

    public function down(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->dropColumn(['nssf_applicable', 'paye_applicable']);
        });
    }
};