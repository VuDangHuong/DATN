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
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('requires_signing')->default(false)->after('is_active');
            // Label mô tả loại tài liệu: bao_cao_thuc_tap, nckh, do_an, v.v.
            $table->string('document_category')->nullable()->after('requires_signing');
            // VD: "Báo cáo thực tập", "Nghiên cứu khoa học"
            $table->string('document_category_label')->nullable()->after('document_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['requires_signing', 'document_category', 'document_category_label']);
        });
    }
};
