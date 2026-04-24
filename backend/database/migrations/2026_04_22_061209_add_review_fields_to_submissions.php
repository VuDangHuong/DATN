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
        Schema::table('submissions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',   // Chờ duyệt (mặc định)
                'approved',  // Đã chấp nhận
                'rejected',  // Đã từ chối
            ])->default('pending')->after('submitted_at');
 
            // Chấm điểm + nhận xét
            $table->decimal('score', 5, 2)->nullable()->after('status');  // VD: 8.50
            $table->text('feedback')->nullable()->after('score');          // Nhận xét của GV
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->after('feedback');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            //
        });
    }
};
