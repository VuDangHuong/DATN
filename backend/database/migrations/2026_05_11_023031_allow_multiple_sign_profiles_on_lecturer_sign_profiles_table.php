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
        $foreignKey = $this->getForeignKeyName();

        if ($foreignKey) {
            DB::statement("ALTER TABLE `lecturer_sign_profiles` DROP FOREIGN KEY `{$foreignKey}`");
        }

        DB::statement("ALTER TABLE `lecturer_sign_profiles` DROP INDEX `lecturer_sign_profiles_lecturer_id_unique`");

        Schema::table('lecturer_sign_profiles', function (Blueprint $table) {
            $table->foreign('lecturer_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();
            $table->index(['lecturer_id', 'is_active']);
            $table->index('cert_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('lecturer_sign_profiles', function (Blueprint $table) {
            $table->dropIndex(['lecturer_id', 'is_active']);
            $table->dropIndex(['cert_expires_at']);
            $table->dropForeign(['lecturer_id']);
            $table->unique('lecturer_id');
            $table->foreign('lecturer_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();
        });
    }

    private function getForeignKeyName(): ?string
    {
        $result = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'lecturer_sign_profiles'
              AND COLUMN_NAME = 'lecturer_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");
        return $result->CONSTRAINT_NAME ?? null;
    }
};
