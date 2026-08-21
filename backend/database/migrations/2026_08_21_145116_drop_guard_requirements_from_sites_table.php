<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Run only after add_post_id_to_roster_assignments_table has copied these
// values onto each site's Main Post — see that migration for the backfill.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['morning_guards_required', 'night_guards_required']);
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->integer('morning_guards_required')->default(0)->after('required_guards');
            $table->integer('night_guards_required')->default(0)->after('morning_guards_required');
        });
    }
};