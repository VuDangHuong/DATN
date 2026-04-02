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
        Schema::create('document_sign_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                  ->constrained('document_sign_requests')
                  ->cascadeOnDelete();

            $table->foreignId('actor_id')
                  ->constrained('users');

            $table->enum('action', [
                'created',
                'admin_approved',
                'admin_rejected',
                'forwarded_to_lecturer',
                'lecturer_viewed',
                'lecturer_signed',
                'lecturer_rejected',
                'completed'
            ]);

            $table->text('note')->nullable();

            $table->string('ip_address', 45)->nullable(); // IPv4 + IPv6

            $table->timestamps();

            // Index để truy vấn nhanh
            // $table->index('request_id');
            // $table->index('actor_id');
            // $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_sign_logs');
    }
};
