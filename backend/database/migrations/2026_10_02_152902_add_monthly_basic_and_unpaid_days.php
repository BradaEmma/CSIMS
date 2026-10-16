<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->decimal('monthly_basic', 12, 2)->nullable()->after('daily_rate');
        });

        Schema::table('payroll_records', function (Blueprint $table) {
            $table->unsignedInteger('unpaid_days')->default(0)->after('days_worked');
        });
    }

    public function down(): void
    {
        Schema::table('guards', function (Blueprint $table) {
            $table->dropColumn('monthly_basic');
        });

        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropColumn('unpaid_days');
        });
    }
};