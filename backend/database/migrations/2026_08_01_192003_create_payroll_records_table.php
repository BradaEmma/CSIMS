<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guard_id')->constrained('guards');
            $table->string('period'); // "2026-08"
            $table->integer('days_worked');
            $table->integer('overtime_days')->default(0);
            $table->decimal('gross_pay', 14, 2);
            $table->decimal('overtime_pay', 14, 2)->default(0);
            $table->decimal('nssf_deduction', 14, 2);
            $table->decimal('paye_deduction', 14, 2);
            $table->decimal('other_deductions_total', 14, 2)->default(0);
            $table->decimal('net_pay', 14, 2);
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->timestamps();

            $table->unique(['guard_id', 'period']); // one payroll record per guard per month
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};