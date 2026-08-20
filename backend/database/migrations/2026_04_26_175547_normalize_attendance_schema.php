<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {

            if (!Schema::hasColumn('attendances', 'shift_type')) {
                $table->string('shift_type')->nullable();
            }

            if (!Schema::hasColumn('attendances', 'shift_date')) {
                $table->date('shift_date')->nullable();
            }

            if (!Schema::hasColumn('attendances', 'check_in_at')) {
                $table->timestamp('check_in_at')->nullable();
            }

            if (!Schema::hasColumn('attendances', 'check_out_at')) {
                $table->timestamp('check_out_at')->nullable();
            }

            if (!Schema::hasColumn('attendances', 'is_late')) {
                $table->boolean('is_late')->default(false);
            }

            if (!Schema::hasColumn('attendances', 'minutes_late')) {
                $table->integer('minutes_late')->default(0);
            }

            if (!Schema::hasColumn('attendances', 'hours_worked')) {
                $table->decimal('hours_worked', 8, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        // optional rollback
    }
};
