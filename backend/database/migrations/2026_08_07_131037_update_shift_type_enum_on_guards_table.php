<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: temporarily widen the enum so both old and new values are valid
        DB::statement("ALTER TABLE guards MODIFY shift_type ENUM('day','night','morning','either') NULL");

        // Step 2: migrate existing data — 'day' meant the same thing as 'morning'
        DB::table('guards')->where('shift_type', 'day')->update(['shift_type' => 'morning']);

        // Step 3: any guard still null gets 'either' as the safe, explicit default
        DB::table('guards')->whereNull('shift_type')->update(['shift_type' => 'either']);

        // Step 4: lock down to the final, correct enum — NOT NULL, no more 'day'
        DB::statement("ALTER TABLE guards MODIFY shift_type ENUM('morning','night','either') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE guards MODIFY shift_type ENUM('day','night','morning','either') NULL");
        DB::table('guards')->where('shift_type', 'morning')->update(['shift_type' => 'day']);
        DB::statement("ALTER TABLE guards MODIFY shift_type ENUM('day','night') NULL");
    }
};