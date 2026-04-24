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
        Schema::create('document_sign_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id')->nullable();

            $table->foreignId('requester_id')
                  ->constrained('users');

            $table->foreignId('class_id')
                  ->constrained('classes');

            $table->foreignId('lecturer_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Thông tin tài liệu
            $table->enum('document_type', ['report', 'slide']);
            $table->string('original_file');
            $table->string('signed_file')->nullable();

            // Thông tin ký số
            $table->string('sign_hash', 64)->nullable();
            $table->string('sign_certificate')->nullable();

            // Trạng thái
            $table->enum('status', [
                'pending',
                'admin_reviewing',
                'forwarded',
                'lecturer_reviewing',
                'signed',
                'rejected_by_admin',
                'rejected_by_lecturer',
                'completed'
            ])->default('pending');

            $table->text('reject_reason')->nullable();

            // Thời gian
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            // Index
            // $table->index('status');
            // $table->index('requester_id');
            // $table->index('lecturer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sign_requests');
    }
};
