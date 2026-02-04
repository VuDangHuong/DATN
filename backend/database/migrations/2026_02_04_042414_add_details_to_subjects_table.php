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
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('major_id')
                  ->after('id') 
                  ->constrained('majors')->cascadeOnDelete(); //xóa ngành thì xóa luôn môn
            $table->integer('credits')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropForeign(['major_id']);
            $table->dropColumn(['major_id', 'credits']);
        });
    }
};
