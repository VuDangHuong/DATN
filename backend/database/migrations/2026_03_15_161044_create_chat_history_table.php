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
        Schema::create('chat_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');

            $table->text('question')->nullable();
            $table->string('file_name')->nullable();
            $table->longText('answer')->nullable();
            $table->json('source_text')->nullable();

            $table->boolean('is_liked')->nullable();
            $table->boolean('is_answered')->default(0);

            $table->string('type', 50)->nullable();

            $table->tinyInteger('star')->default(0);
            $table->tinyInteger('mail_sent')->default(0);

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_history');
    }
};
