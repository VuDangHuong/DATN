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
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            // Liên kết với lớp học phần [cite: 180, 232]
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            // Liên kết với nhóm (có thể null nếu sinh viên chưa có nhóm) [cite: 240]
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('set null');
            // Điểm số với định dạng decimal(4,2) như yêu cầu SQL
            $table->decimal('score', 4, 2)->nullable()->comment('Điểm số của sinh viên');
            // Ghi chú lý do tại sao sinh viên được điểm như vậy 
            $table->text('lecturer_note')->nullable();
            // Xác nhận điểm cuối kỳ theo mô tả logic của bạn
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
