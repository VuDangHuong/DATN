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
        Schema::create('sign_profile_deactivation_requests', function (Blueprint $table) {
             $table->id();
 
            $table->foreignId('lecturer_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('profile_id')
                  ->constrained('lecturer_sign_profiles')
                  ->cascadeOnDelete();
 
            $table->text('reason');                              // Lý do GV nhập
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');
 
            $table->foreignId('admin_id')->nullable()
                  ->constrained('users');                        // Admin duyệt
            $table->text('admin_note')->nullable();              // Ghi chú admin khi reject
            $table->timestamp('resolved_at')->nullable();        // Thời điểm admin duyệt/từ chối
 
            $table->timestamps();
 
            $table->index(['lecturer_id', 'status']);
            $table->index('status');
        });
        Schema::table('lecturer_sign_profiles', function (Blueprint $table) {
            $table->boolean('pending_deactivation')
                  ->default(false)
                  ->after('is_active');
            //True = đang chờ admin duyệt vô hiệu hóa → không ký được
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sign_profile_deactivation_requests');
        Schema::table('lecturer_sign_profiles', function (Blueprint $table) {
            $table->dropColumn('pending_deactivation');
        });
    }
};
