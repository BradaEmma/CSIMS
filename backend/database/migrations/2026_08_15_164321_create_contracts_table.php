<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('monthly_fee', 14, 2)->nullable();
            $table->text('terms')->nullable();
            $table->enum('status', ['active', 'expired', 'terminated'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};