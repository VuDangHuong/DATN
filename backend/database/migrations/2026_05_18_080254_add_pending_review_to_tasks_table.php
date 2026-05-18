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
        DB::statement("ALTER TABLE tasks 
            MODIFY COLUMN status ENUM('todo','doing','pending_review','done','late') 
            NOT NULL DEFAULT 'todo'");

        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('submitted_for_review_at')->nullable()->after('actual_finish_date');
            $table->text('submission_note')->nullable()->after('submitted_for_review_at');
 
            // Khi leader duyệt
            $table->foreignId('reviewed_by')->nullable()
                  ->after('submission_note')
                  ->constrained('users');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'submitted_for_review_at',
                'submission_note',
                'reviewed_by',
                'reviewed_at',
                'review_note',
            ]);
        });
        DB::statement("ALTER TABLE tasks 
            MODIFY COLUMN status ENUM('todo','doing','done','late') 
            NOT NULL DEFAULT 'todo'");
    }
};
