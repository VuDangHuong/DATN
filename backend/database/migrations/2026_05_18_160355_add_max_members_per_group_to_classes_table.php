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
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_members_per_group')
                  ->nullable()
                  ->default(5)
                  ->after('max_members')
                  ->comment('Số thành viên tối đa MỖI NHÓM. Default 5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('max_members_per_group');
        });
    }
};
