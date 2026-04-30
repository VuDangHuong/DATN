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
        Schema::table('document_sign_logs', function (Blueprint $table) {
            DB::statement("ALTER TABLE document_sign_logs MODIFY COLUMN action VARCHAR(50) NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_sign_logs', function (Blueprint $table) {
            DB::statement("ALTER TABLE document_sign_logs MODIFY COLUMN action ENUM('created','admin_reviewing','forwarded_to_lecturer','lecturer_reviewing','signed','admin_rejected','lecturer_rejected','completed') NOT NULL");
        });
    }
};
