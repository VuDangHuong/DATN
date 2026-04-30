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
        Schema::table('sign_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE document_sign_requests MODIFY COLUMN document_type VARCHAR(50) NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sign_requests', function (Blueprint $table) {
            DB::statement("ALTER TABLE document_sign_requests MODIFY COLUMN document_type ENUM('report','slide') NOT NULL");
        });
    }
};
