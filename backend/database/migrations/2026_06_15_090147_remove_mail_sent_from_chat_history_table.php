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
        Schema::table('chat_history', function (Blueprint $table) {
            if (Schema::hasColumn('chat_history', 'mail_sent')) {
                $table->dropColumn('mail_sent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_history', function (Blueprint $table) {
            $table->tinyInteger('mail_sent')->default(0);
        });
    }
};
