<?php

// app/Services/DocumentSignService.php
namespace App\Services;
use App\Models\Sign\DocumentSignLog;
use Illuminate\Http\Request;
class DocumentSignService
{
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
     * Xác minh chữ ký số bằng public key của GV
     */
    public function verifySignature($file, string $publicKeyPem, string $signature): bool
    {
        $data   = file_get_contents($file->getRealPath());
        $pubKey = openssl_pkey_get_public($publicKeyPem);
        $result = openssl_verify($data, base64_decode($signature), $pubKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
 
    /**
     * Tạo hash SHA256 cho file để client verify sau khi download
     */
    public function generateFileHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }
 
    /**
     * Kiểm tra document_category có hợp lệ không
     */
    public function isValidCategory(string $category): bool
    {
        return array_key_exists($category, $this->getCategories());
    }
 
    /**
     * Danh sách loại tài liệu cần ký số
     */
    public function getCategories(): array
    {
        return [
            'bao_cao_thuc_tap'  => 'Báo cáo thực tập',
            'nckh'              => 'Nghiên cứu khoa học',
            'do_an_tot_nghiep'  => 'Đồ án tốt nghiệp',
            'bao_cao_du_an'     => 'Báo cáo dự án',
            'khoa_luan'         => 'Khóa luận',
        ];
    }
}