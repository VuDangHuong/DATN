<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('skill_users');
        Schema::dropIfExists('skills');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['hard', 'soft', 'domain']);
            $table->timestamps();
        });

        Schema::create('skill_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->integer('level')->nullable();
            $table->timestamps();
        });
    }
};
