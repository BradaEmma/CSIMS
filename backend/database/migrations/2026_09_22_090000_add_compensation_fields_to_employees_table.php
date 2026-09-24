<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('pay_type', ['daily', 'monthly'])->default('daily')->after('position');
            $table->decimal('monthly_salary', 14, 2)->nullable()->after('pay_type');
            $table->boolean('nssf_applicable')->default(true)->after('monthly_salary');
            $table->boolean('paye_applicable')->default(true)->after('nssf_applicable');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['pay_type', 'monthly_salary', 'nssf_applicable', 'paye_applicable']);
        });
    }
};