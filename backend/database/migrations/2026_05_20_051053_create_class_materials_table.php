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
        Schema::create('class_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();
 
            $table->foreignId('uploaded_by')
                  ->constrained('users');
 
            $table->string('title');               // "Chương 1 - Giới thiệu"
            $table->text('description')->nullable();
            $table->string('category', 50)->default('lecture');
 
            // Tracking copy
            $table->foreignId('copied_from')->nullable()
                  ->constrained('class_materials')
                  ->nullOnDelete();
            $table->timestamps();
            $table->index(['class_id', 'category']);
            $table->index('uploaded_by');
        });

        Schema::create('class_material_files', function (Blueprint $table) {
            $table->id();
 
            $table->foreignId('material_id')
                  ->constrained('class_materials')
                  ->cascadeOnDelete();
 
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension', 20);
            $table->string('file_mime', 100);
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('download_count')->default(0);
 
            $table->timestamps();
 
            $table->index('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_material_files');
        Schema::dropIfExists('class_materials');
    }
};
