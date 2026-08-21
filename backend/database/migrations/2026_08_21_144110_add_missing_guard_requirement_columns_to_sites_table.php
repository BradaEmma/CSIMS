<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// These two columns were already in use throughout the app (Site model,
// SiteController validation, RosterEngineService, Dashboard controllers,
// Roster.jsx) but no migration in this repo ever created them — they were
// added directly to the dev database by hand at some point. This migration
// closes that gap so migration history matches reality on every environment,
// before the next migration moves this data onto the new posts table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'morning_guards_required')) {
                $table->integer('morning_guards_required')->default(0)->after('required_guards');
            }
            if (!Schema::hasColumn('sites', 'night_guards_required')) {
                $table->integer('night_guards_required')->default(0)->after('morning_guards_required');
            }
        });
    }

    public function down(): void
    {
        // Intentionally a no-op: these columns are dropped by the later
        // "drop_guard_requirements_from_sites_table" migration instead, since
        // this migration only exists to catch environments up to date.
    }
};