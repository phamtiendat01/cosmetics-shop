<?php

namespace App\Services\Blockchain;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    /**
     * Upload data to IPFS (Pinata)
     */
    public function uploadToIPFS(array $data): ?array
    {
        if (!config('blockchain.ipfs_enabled')) {
            return null;
        }

        $apiKey = config('blockchain.pinata.api_key');
        $secretKey = config('blockchain.pinata.secret_key');

        if (!$apiKey || !$secretKey) {
            Log::warning('Pinata credentials not configured');
            return null;
        }

        try {
            // Pinata pinJSONToIPFS API - dùng cho JSON data
            // Tắt SSL verification cho development (Windows/Laragon)
            $response = Http::withOptions([
                'verify' => false, // Tắt SSL verification cho development
            ])->withHeaders([
                'Content-Type' => 'application/json',
                'pinata_api_key' => $apiKey,
                'pinata_secret_api_key' => $secretKey,
            ])->post('https://api.pinata.cloud/pinning/pinJSONToIPFS', [
                'pinataContent' => $data,
                'pinataMetadata' => [
                    'name' => 'cosmechain-certificate-' . time(),
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'ipfs_hash' => $result['IpfsHash'] ?? null,
                    'ipfs_url' => 'https://gateway.pinata.cloud/ipfs/' . ($result['IpfsHash'] ?? ''),
                ];
            }

            // Log chi tiết lỗi để debug
            Log::error('IPFS upload failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'headers' => $response->headers(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('IPFS upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Generate certificate hash
     */
    public function generateCertificateHash(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash('sha256', $json);
    }

    /**
     * Verify certificate hash
     */
    public function verifyCertificate(string $hash, array $data): bool
    {
        $expectedHash = $this->generateCertificateHash($data);
        return hash_equals($expectedHash, $hash);
    }
}
