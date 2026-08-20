<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guard_id')->constrained('guards');
            $table->foreignId('payroll_deduction_type_id')->constrained('payroll_deduction_types');
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->foreignId('applied_by')->constrained('users');
            $table->string('period');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deductions');
    }
};