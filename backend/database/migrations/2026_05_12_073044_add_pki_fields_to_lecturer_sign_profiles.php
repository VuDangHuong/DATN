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
        Schema::table('lecturer_sign_profiles', function (Blueprint $table) {
            // Encrypted private key (encrypt bằng signing_password riêng)
            $table->text('encrypted_private_key')->nullable()->after('public_key');
 
            // Salt + IV cho AES-256-CBC
            $table->string('encryption_salt', 64)->nullable()->after('encrypted_private_key');
            $table->string('encryption_iv', 64)->nullable()->after('encryption_salt');
 
            // Hash của signing password (verify trước khi decrypt)
            $table->string('signing_password_hash')->nullable()->after('encryption_iv');
 
            // Cert metadata parsed từ X.509
            $table->string('subject_cn')->nullable()->after('certificate_serial');
            $table->string('issuer_cn')->nullable()->after('subject_cn');
            $table->string('algorithm', 50)->nullable()->after('issuer_cn');
            $table->timestamp('cert_valid_from')->nullable()->after('algorithm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecturer_sign_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'encrypted_private_key',
                'encryption_salt',
                'encryption_iv',
                'signing_password_hash',
                'subject_cn',
                'issuer_cn',
                'algorithm',
                'cert_valid_from',
            ]);
        });
    }
};
