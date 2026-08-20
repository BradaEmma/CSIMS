<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->boolean('is_double_shift')->default(false)->after('is_overtime');
            $table->foreignId('consent_confirmed_by')->nullable()->after('is_double_shift')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->dropForeign(['consent_confirmed_by']);
            $table->dropColumn(['is_double_shift', 'consent_confirmed_by']);
        });
    }
};