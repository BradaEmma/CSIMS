<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_amount', 14, 2);
            $table->decimal('max_amount', 14, 2)->nullable(); // null = no upper limit (top bracket)
            $table->decimal('base_tax', 14, 2)->default(0);   // flat amount added before the rate applies
            $table->decimal('rate_percentage', 5, 2);         // e.g. 9.00, 20.00, 30.00
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_brackets');
    }
};