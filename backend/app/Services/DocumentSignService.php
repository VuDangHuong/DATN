<?php

// app/Services/DocumentSignService.php
namespace App\Services;
use App\Models\Sign\DocumentSignLog;
use Illuminate\Http\Request;
class DocumentSignService
{
    /**
     * Ghi log mỗi khi status thay đổi
     */
    public function log(int $requestId, int $actorId, string $action, ?string $note = null): void
    {
        DocumentSignLog::create([
            'request_id' => $requestId,
            'actor_id'   => $actorId,
            'action'     => $action,
            'note'       => $note,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Xác minh hash file upload khớp với hash client gửi lên
     */
    public function verifyFileHash($uploadedFile, string $clientHash): bool
    {
        $serverHash = hash_file('sha256', $uploadedFile->getRealPath());
        return hash_equals($serverHash, strtolower($clientHash));
    }

    /**
     * Xác minh chữ ký số bằng public key của GV (tuỳ chọn nâng cao)
     */
    public function verifySignature($file, string $publicKeyPem, string $signature): bool
    {
        $data    = file_get_contents($file->getRealPath());
        $pubKey  = openssl_pkey_get_public($publicKeyPem);
        $result  = openssl_verify($data, base64_decode($signature), $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
}