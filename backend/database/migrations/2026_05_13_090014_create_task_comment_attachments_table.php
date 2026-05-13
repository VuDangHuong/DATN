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
        Schema::create('task_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')
                  ->constrained('task_comments')
                  ->cascadeOnDelete();
 
            $table->string('file_path');       // storage relative path
            $table->string('file_name');       // tên file gốc
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size'); // bytes
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->index('comment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_comment_attachments');
    }
};
