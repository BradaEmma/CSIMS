<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('roster_assignment_id')->nullable()->constrained('roster_assignments')->nullOnDelete();
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('incident_type_id')->constrained('incident_types');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->dateTime('occurred_at');
            $table->enum('status', ['open', 'under_review', 'resolved', 'closed'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};