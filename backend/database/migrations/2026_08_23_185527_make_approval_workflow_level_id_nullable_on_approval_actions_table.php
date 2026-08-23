<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE approval_actions MODIFY approval_workflow_level_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE approval_actions MODIFY approval_workflow_level_id BIGINT UNSIGNED NOT NULL');
    }
};