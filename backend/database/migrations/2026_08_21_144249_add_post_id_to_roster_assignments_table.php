<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Give every existing site a default "Main Post" carrying its
        //    current per-shift requirements, so no existing data is lost.
        $sites = DB::table('sites')->get();

        $sitePostIds = [];

        foreach ($sites as $site) {
            $sitePostIds[$site->id] = DB::table('posts')->insertGetId([
                'site_id' => $site->id,
                'name' => 'Main Post',
                'morning_guards_required' => $site->morning_guards_required ?? 0,
                'night_guards_required' => $site->night_guards_required ?? 0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Add post_id to roster_assignments as nullable first, so we can
        //    backfill existing rows before making it required.
        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->foreignId('post_id')->nullable()->after('site_id')->constrained('posts')->cascadeOnDelete();
        });

        // 3. Backfill every existing assignment to its site's Main Post.
        foreach ($sitePostIds as $siteId => $postId) {
            DB::table('roster_assignments')
                ->where('site_id', $siteId)
                ->update(['post_id' => $postId]);
        }

        // Note: post_id stays nullable at the DB level (changing it to NOT
        // NULL here would need doctrine/dbal, which isn't installed and
        // isn't worth adding for this). Every row is backfilled above, and
        // RosterAssignment always sets post_id going forward — enforced at
        // the application layer instead.
    }

    public function down(): void
    {
        Schema::table('roster_assignments', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->dropColumn('post_id');
        });
    }
};