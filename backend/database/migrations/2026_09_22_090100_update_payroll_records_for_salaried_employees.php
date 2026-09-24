<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->foreignId('guard_id')->nullable()->change();
            $table->integer('days_worked')->nullable()->change();
            $table->integer('overtime_days')->nullable()->change();
            $table->unique(['employee_id', 'period'], 'payroll_records_employee_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropUnique('payroll_records_employee_period_unique');
            $table->foreignId('guard_id')->nullable(false)->change();
            $table->integer('days_worked')->nullable(false)->change();
            $table->integer('overtime_days')->nullable(false)->change();
        });
    }
};