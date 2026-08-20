<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roster_assignments', function (Blueprint $table) {
            // Add the new index FIRST — it also starts with guard_id, so it
            // can immediately take over as the supporting index for the
            // guard_id foreign key, letting us safely drop the old one next.
            $table->unique(['guard_id', 'date', 'shift']);
        });

        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->dropUnique(['guard_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->unique(['guard_id', 'date']);
        });

        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->dropUnique(['guard_id', 'date', 'shift']);
        });
    }
};