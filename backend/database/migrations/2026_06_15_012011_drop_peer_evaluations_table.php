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
        Schema::dropIfExists('peer_evaluations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('peer_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluator_id')->constrained('users');
            $table->foreignId('evaluated_id')->constrained('users');
            $table->foreignId('class_id')->constrained();
            $table->integer('score');
            $table->text('comment')->nullable();
            $table->string('phase')->nullable();
            $table->timestamps();
        });
    }
};
