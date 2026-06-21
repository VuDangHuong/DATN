<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dọn dẹp các cột và bảng không còn được sử dụng trong code.
     *
     * Nhóm A - cột thừa (bảng vẫn dùng, cột không được đọc/ghi ở đâu):
     *   - groups.invitation_code              (không có chức năng join bằng mã)
     *   - classes.group_registration_deadline (chỉ hiển thị, không có logic check hạn)
     *   - topics.name_vi / name_en / technology / lecturer_comment (chưa được code tới)
     *   - document_sign_requests.forwarded_at (chỉ còn trong code đã comment)
     *
     * Nhóm B - bảng của feature chưa implement:
     *   - recruitment_posts
     *   - deadline_extensions
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'invitation_code')) {
                $table->dropColumn('invitation_code');
            }
        });

        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'group_registration_deadline')) {
                $table->dropColumn('group_registration_deadline');
            }
        });

        Schema::table('topics', function (Blueprint $table) {
            foreach (['name_vi', 'name_en', 'technology', 'lecturer_comment'] as $col) {
                if (Schema::hasColumn('topics', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('document_sign_requests', function (Blueprint $table) {
            if (Schema::hasColumn('document_sign_requests', 'forwarded_at')) {
                $table->dropColumn('forwarded_at');
            }
        });

        // Nhóm B: drop bảng. deadline_extensions tham chiếu tasks; recruitment_posts tham chiếu groups.
        Schema::dropIfExists('deadline_extensions');
        Schema::dropIfExists('recruitment_posts');
    }

    /**
     * Khôi phục lại (rollback) cấu trúc ban đầu.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('invitation_code')->nullable();
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dateTime('group_registration_deadline')->nullable();
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->string('name_vi')->after('group_id');
            $table->string('name_en')->nullable()->after('name_vi');
            $table->text('technology')->after('description');
            $table->text('lecturer_comment')->nullable()->after('status');
        });

        Schema::table('document_sign_requests', function (Blueprint $table) {
            $table->timestamp('forwarded_at')->nullable();
        });

        if (!Schema::hasTable('recruitment_posts')) {
            Schema::create('recruitment_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('content');
                $table->boolean('is_open')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('deadline_extensions')) {
            Schema::create('deadline_extensions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->text('reason');
                $table->dateTime('requested_deadline');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->timestamps();
            });
        }
    }
};
