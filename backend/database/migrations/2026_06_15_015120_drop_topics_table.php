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
         Schema::dropIfExists('topics');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->onDelete('cascade');
                $table->string('name_vi');
                $table->string('name_en')->nullable();
                $table->text('description');
                $table->text('technology');
                $table->enum('status', ['pending', 'approved', 'rejected', 'correction_required'])->default('pending');
                $table->text('lecturer_comment')->nullable();
                $table->timestamps();
            });
        }
    }
};
