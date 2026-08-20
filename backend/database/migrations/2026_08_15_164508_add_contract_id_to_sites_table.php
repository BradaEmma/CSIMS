<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('id')->constrained('contracts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });
    }
};