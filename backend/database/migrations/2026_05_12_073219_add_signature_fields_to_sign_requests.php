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
        Schema::table('document_sign_requests', function (Blueprint $table) {
            $table->string('file_hash', 128)->nullable()->after('sign_hash');
 
            // RSA signature (base64 encoded)
            $table->text('signature')->nullable()->after('file_hash');
 
            // Snapshot public key tại thời điểm ký (để verify sau này, kể cả khi GV đổi cert)
            $table->text('signer_public_key')->nullable()->after('signature');
 
            // Algorithm dùng (sha256WithRSAEncryption)
            $table->string('signature_algorithm', 50)->nullable()->after('signer_public_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_sign_requests', function (Blueprint $table) {
            $table->dropColumn([
                'file_hash', 'signature', 'signer_public_key', 'signature_algorithm',
            ]);
        });
    }
};
