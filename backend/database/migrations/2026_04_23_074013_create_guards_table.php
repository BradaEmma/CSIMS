<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('guards', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('phone')->unique();
            $table->string('national_id')->unique()->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('shift_type', ['day', 'night'])->nullable();

            $table->foreignId('site_id')
                ->nullable()
                ->constrained('sites')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guards');
    }
};