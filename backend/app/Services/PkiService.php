<?php

namespace App\Services;

class PkiService
{
    /**
     * Parse X.509 certificate → trả về metadata
     *
     * @param string $pemCert PEM-encoded certificate
     * @return array|null
     */
    public function parseCertificate(string $pemCert): ?array
{
    $resource = @openssl_x509_read($pemCert);
    if (!$resource) return null;
 
    $parsed = openssl_x509_parse($resource);
    if (!$parsed) return null;
 
    $publicKeyResource = openssl_pkey_get_public($resource);
    $publicKeyDetails  = openssl_pkey_get_details($publicKeyResource);
    $publicKeyPem      = $publicKeyDetails['key'] ?? null;
 
    return [
        'serial'         => $this->extractSerial($parsed),
        'subject_cn'     => $parsed['subject']['CN'] ?? null,
        'subject_o'      => $parsed['subject']['O'] ?? null,
        'subject_full'   => $this->dnToString($parsed['subject'] ?? []),
        'issuer_cn'      => $parsed['issuer']['CN'] ?? null,
        'issuer_o'       => $parsed['issuer']['O'] ?? null,
        'issuer_full'    => $this->dnToString($parsed['issuer'] ?? []),
        'valid_from'     => isset($parsed['validFrom_time_t']) ? date('Y-m-d H:i:s', $parsed['validFrom_time_t']) : null,
        'valid_to'       => isset($parsed['validTo_time_t']) ? date('Y-m-d H:i:s', $parsed['validTo_time_t']) : null,
        'signature_algo' => $parsed['signatureTypeSN'] ?? $parsed['signatureTypeLN'] ?? null,
        'key_algorithm'  => $this->getKeyAlgorithm($publicKeyDetails),
        'key_bits'       => $publicKeyDetails['bits'] ?? null,
        'public_key_pem' => $publicKeyPem,
        'is_expired'     => isset($parsed['validTo_time_t']) && $parsed['validTo_time_t'] < time(),
    ];
}

    /**
     * Kiểm tra private key có khớp public key không
     * Bằng cách: ký 1 chuỗi test rồi verify
     */
    public function verifyKeyPair(string $privateKeyPem, string $publicKeyPem, ?string $privateKeyPassword = null): bool
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem, $privateKeyPassword);
        if (!$privateKey) return false;

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if (!$publicKey) return false;

        // Test sign + verify
        $testData = 'edugroup_key_pair_verification_' . random_bytes(16);
        $signature = '';

        $signed = openssl_sign($testData, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signed) return false;

        $verified = openssl_verify($testData, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        return $verified === 1;
    }

    /**
     * Encrypt private key bằng password (AES-256-CBC + PBKDF2)
     *
     * @return array{encrypted: string, salt: string, iv: string}
     */
    public function encryptPrivateKey(string $privateKeyPem, string $password): array
    {
        $salt = random_bytes(32);
        $iv   = random_bytes(16); // AES-256-CBC dùng IV 16 bytes

        // Derive 256-bit key từ password
        $key = hash_pbkdf2('sha256', $password, $salt, 100_000, 32, true);

        $encrypted = openssl_encrypt(
            $privateKeyPem,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encrypt private key failed: ' . openssl_error_string());
        }

        return [
            'encrypted' => base64_encode($encrypted),
            'salt'      => bin2hex($salt),
            'iv'        => bin2hex($iv),
        ];
    }

    /**
     * Decrypt private key bằng password + salt + iv
     */
    public function decryptPrivateKey(
        string $encryptedBase64,
        string $saltHex,
        string $ivHex,
        string $password
    ): ?string {
        $salt = hex2bin($saltHex);
        $iv   = hex2bin($ivHex);
        $key  = hash_pbkdf2('sha256', $password, $salt, 100_000, 32, true);

        $decrypted = openssl_decrypt(
            base64_decode($encryptedBase64),
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted === false ? null : $decrypted;
    }

    /**
     * Ký data bằng private key (RSA-SHA256)
     *
     * @return string Base64-encoded signature
     */
    public function signData(string $data, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if (!$privateKey) {
            throw new \RuntimeException('Invalid private key: ' . openssl_error_string());
        }

        $signature = '';
        $success   = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new \RuntimeException('Sign failed: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    /**
     * Verify signature
     *
     * @param string $data           Data gốc
     * @param string $signatureB64   Signature base64
     * @param string $publicKeyPem   Public key PEM
     * @return bool
     */
    public function verifySignature(string $data, string $signatureB64, string $publicKeyPem): bool
    {
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if (!$publicKey) return false;

        $signature = base64_decode($signatureB64);
        $result    = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    /**
 * Generate cặp RSA-2048 key + self-signed cert (cho demo)
 */
public function generateTestKeyPair(array $info, int $validDays = 365): array
{
    $configPath = $this->findOpensslConfig();
 
    if (!$configPath) {
        throw new \RuntimeException(
            'Không tìm thấy openssl.cnf. Tạo file tại storage/openssl.cnf.'
        );
    }
 
    $config = [
        'config'           => $configPath,
        'digest_alg'       => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
 
    // 1. Generate private key
    $privateKey = openssl_pkey_new($config);
    if ($privateKey === false) {
        throw new \RuntimeException('openssl_pkey_new failed: ' . $this->getAllOpenSSLErrors());
    }
 
    // 2. Export private key
    if (!openssl_pkey_export($privateKey, $privateKeyPem, null, $config)) {
        throw new \RuntimeException('openssl_pkey_export failed: ' . $this->getAllOpenSSLErrors());
    }
 
    // 3. Public key
    $details = openssl_pkey_get_details($privateKey);
    $publicKeyPem = $details['key'];
 
    // 4. DN
    $dn = [
        'commonName'       => $info['cn'] ?? 'Test User',
        'organizationName' => $info['o']  ?? 'EduGroup',
        'countryName'      => $info['c']  ?? 'VN',
    ];
    if (!empty($info['email'])) {
        $dn['emailAddress'] = $info['email'];
    }
 
    // 5. CSR
    $csr = openssl_csr_new($dn, $privateKey, $config);
    if ($csr === false) {
        throw new \RuntimeException('openssl_csr_new failed: ' . $this->getAllOpenSSLErrors());
    }
 
    // ✅ 6. Sign với serial RANDOM (thay vì để default = 0)
    $randomSerial = $this->generateRandomSerial();
 
    $cert = openssl_csr_sign($csr, null, $privateKey, $validDays, $config, $randomSerial);
    if ($cert === false) {
        throw new \RuntimeException('openssl_csr_sign failed: ' . $this->getAllOpenSSLErrors());
    }
 
    // 7. Export cert
    if (!openssl_x509_export($cert, $certPem)) {
        throw new \RuntimeException('openssl_x509_export failed: ' . $this->getAllOpenSSLErrors());
    }
 
    // ✅ 8. Parse với fallback đầy đủ
    $parsed = openssl_x509_parse($cert);
    $serial = $this->extractSerial($parsed) ?? dechex($randomSerial);
 
    return [
        'private_key' => $privateKeyPem,
        'public_key'  => $publicKeyPem,
        'certificate' => $certPem,
        'serial'      => $serial,
        'subject_cn'  => $dn['commonName'],
        'issuer_cn'   => $dn['commonName'],
        'valid_from'  => date('Y-m-d H:i:s', $parsed['validFrom_time_t']),
        'valid_to'    => date('Y-m-d H:i:s', $parsed['validTo_time_t']),
    ];
}

private function extractSerial(array $parsed): ?string
{
    // 1. Ưu tiên serialNumberHex (PHP 8.1+)
    if (!empty($parsed['serialNumberHex'])
        && $parsed['serialNumberHex'] !== '0'
        && $parsed['serialNumberHex'] !== '00') {
        return strtoupper($parsed['serialNumberHex']);
    }
 
    // 2. Fallback: serialNumber (integer/string)
    if (!empty($parsed['serialNumber'])) {
        $s = $parsed['serialNumber'];
 
        // Nếu là số (0, "0"), bỏ qua
        if ($s === 0 || $s === '0' || $s === '') {
            return null;
        }
 
        // Nếu là số nguyên, convert sang hex
        if (is_numeric($s)) {
            return strtoupper(dechex((int) $s));
        }
 
        return strtoupper($s);
    }
 
    return null;
}
 
/**
 * ✅ Tạo serial random 16 bytes (đủ unique, không trùng)
 */
private function generateRandomSerial(): int
{
    // Random 53-bit integer (max safe int trong PHP/JS)
    return random_int(1_000_000_000, PHP_INT_MAX);
}
/**
 * Tìm openssl.cnf trên hệ thống
 */
private function findOpensslConfig(): ?string
{
    // 0. App's own config (highest priority)
    $appConfig = storage_path('openssl.cnf');
    if (file_exists($appConfig)) return $appConfig;
 
    // 1. Env var
    $env = getenv('OPENSSL_CONF');
    if ($env && file_exists($env)) return $env;
 
    // 2. PHP dir + extras/ssl (Laragon pattern)
    $phpDir = dirname(PHP_BINARY);
    $tryPath = $phpDir . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR
             . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
    if (file_exists($tryPath)) return $tryPath;
 
    // 3. Common locations
    $candidates = [
        'C:\laragon\bin\php\php-8.4\extras\ssl\openssl.cnf',
        'C:\laragon\bin\php\php-8.3\extras\ssl\openssl.cnf',
        'C:\laragon\bin\php\php-8.2\extras\ssl\openssl.cnf',
        'C:\laragon\bin\php\php-8.1\extras\ssl\openssl.cnf',
        'C:\xampp\php\extras\openssl\openssl.cnf',
        '/etc/ssl/openssl.cnf',
        '/usr/local/etc/openssl/openssl.cnf',
        '/usr/local/etc/openssl@3/openssl.cnf',
    ];
 
    foreach ($candidates as $path) {
        if (file_exists($path)) return $path;
    }
 
    return null;
}
 
/**
 * Helper lấy tất cả OpenSSL errors
 */
    private function getAllOpenSSLErrors(): string
    {
        $errors = [];
        while ($msg = openssl_error_string()) {
            $errors[] = $msg;
        }
        return empty($errors) ? 'No OpenSSL error message available' : implode(' | ', $errors);
    }

    /**
     * Hash file content (SHA-256)
     */
    public function hashFile(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Hash string (SHA-256)
     */
    public function hashString(string $content): string
    {
        return hash('sha256', $content);
    }

    // ── Private helpers ─────────────────────────────────

    private function dnToString(array $dn): string
    {
        $parts = [];
        foreach (['CN', 'O', 'OU', 'C', 'ST', 'L', 'emailAddress'] as $key) {
            if (!empty($dn[$key])) {
                $parts[] = "{$key}={$dn[$key]}";
            }
        }
        return implode(', ', $parts);
    }

    private function getKeyAlgorithm(array $details): string
    {
        $typeMap = [
            OPENSSL_KEYTYPE_RSA => 'RSA',
            OPENSSL_KEYTYPE_DSA => 'DSA',
            OPENSSL_KEYTYPE_EC  => 'EC',
            OPENSSL_KEYTYPE_DH  => 'DH',
        ];
        return $typeMap[$details['type'] ?? -1] ?? 'Unknown';
    }
}