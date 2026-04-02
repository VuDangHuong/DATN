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
        Schema::create('lecturer_sign_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Thông tin chứng thư số
            $table->text('public_key'); // PEM public key
            $table->string('certificate_serial')->nullable();
            $table->json('certificate_meta')->nullable();
            $table->timestamp('cert_expires_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Index
            // $table->index('is_active');
            // $table->index('cert_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturer_sign_profiles');
    }
};
