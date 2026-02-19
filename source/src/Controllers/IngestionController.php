<?php

namespace App\Controllers;

use App\Config;
use App\Models\Storage;
use App\Utils\Normalizer;
use App\Utils\MetadataParser;
use ZipArchive;

class IngestionController
{
    private Config $config;
    private Storage $storage;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->storage = new Storage();
    }


    public function upload(): void
    {
        header('Content-Type: application/json');

        try {
            if (!isset($_FILES['file'])) {
                throw new \RuntimeException("No file uploaded");
            }

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException("Upload failed");
            }

            if (!preg_match('/\.whl$/i', $file['name'])) {
                throw new \RuntimeException("Only .whl files are allowed for upload");
            }

            $result = $this->processArtifact($file['tmp_name'], $file['name'], null);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function url(): void
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!isset($input['url'])) {
                throw new \RuntimeException("No URL provided");
            }

            $tmpPath = $this->downloadUrl($input['url']);
            $filename = basename(parse_url($input['url'], PHP_URL_PATH));
            
            $result = $this->processArtifact($tmpPath, $filename, $input['url']);
            @unlink($tmpPath);
            
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\App\Exceptions\NetworkException $e) {
            http_response_code($e->getHttpCode());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'redirect_chain' => $e->getRedirectResponses(),
            ]);
        } catch (\App\Exceptions\AppException $e) {
            http_response_code($e->getHttpCode());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function downloadUrl(string $url): string
    {
        $tmpDir = $this->config->get('STORAGE_ROOT') . '/tmp/imports';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        
        $tmpPath = $tmpDir . '/' . uniqid('import_');
        $maxSize = $this->config->get('MAX_IMPORT_SIZE_MB') * 1024 * 1024;
        $maxRedirects = $this->config->get('IMPORT_MAX_REDIRECTS');
        $timeout = $this->config->get('IMPORT_TIMEOUT_SEC');

        $redirectChain = [];
        $currentUrl = $url;
        $redirectCount = 0;
        $has307 = false;

        while (true) {
            $ch = curl_init($currentUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_MAXFILESIZE => $maxSize,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            $redirectChain[] = [
                'url' => $currentUrl,
                'http_code' => $httpCode,
                'headers' => $headers,
                'body_preview' => $body,
            ];

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \App\Exceptions\NetworkException(
                    "cURL error: {$error}",
                    $redirectChain
                );
            }

            curl_close($ch);

            if ($httpCode === 307) {
                $has307 = true;
            }

            if ($httpCode >= 300 && $httpCode < 400) {
                if (preg_match('/^Location:\s*(.+?)$/im', $headers, $matches)) {
                    $redirectCount++;
                    $currentUrl = trim($matches[1]);
                    continue;
                } else {
                    throw new \App\Exceptions\AppException(
                        "Redirect response (HTTP {$httpCode}) missing Location header",
                        400
                    );
                }
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                if ($redirectCount > $maxRedirects && $has307) {
                    throw new \App\Exceptions\NetworkException(
                        "Too many redirects (>{$maxRedirects})",
                        $redirectChain
                    );
                }
                
                if ($redirectCount > $maxRedirects) {
                    throw new \App\Exceptions\AppException(
                        "Problem accessing the file: too many redirects",
                        400
                    );
                }

                return $this->downloadFile($currentUrl, $tmpPath, $maxSize, $timeout);
            }
            
            throw new \App\Exceptions\AppException(
                "Invalid content: HTTP {$httpCode}",
                $httpCode >= 400 && $httpCode < 500 ? 400 : 502
            );
        }
    }

    private function downloadFile(string $url, string $tmpPath, int $maxSize, int $timeout): string
    {
        $ch = curl_init($url);
        $fp = fopen($tmpPath, 'wb');

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_MAXFILESIZE => $maxSize,
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        if (!$success || $httpCode < 200 || $httpCode >= 300) {
            @unlink($tmpPath);
            throw new \App\Exceptions\AppException(
                "Download failed: HTTP {$httpCode}" . ($error ? " - {$error}" : ""),
                $httpCode >= 400 && $httpCode < 500 ? 400 : 502
            );
        }

        $actualSize = filesize($tmpPath);
        if ($actualSize > $maxSize) {
            @unlink($tmpPath);
            throw new \App\Exceptions\AppException(
                "File too large: {$actualSize} bytes (max: {$maxSize})",
                400
            );
        }

        return $tmpPath;
    }

    private function processArtifact(string $tmpPath, string $filename, ?string $sourceUrl): array
    {
        if (!$this->verifyRecordSignature($tmpPath, $filename)) {
            throw new \RuntimeException("Signature verification failed: RECORD file not signed with trusted key");
        }

        $sha256 = hash_file('sha256', $tmpPath);
        $size = filesize($tmpPath);

        if ($this->storage->fileExistsBySha256($sha256)) {
            $metadata = MetadataParser::parse($tmpPath, $filename);
            $displayName = $metadata['name'] ?? 'unknown';
            $normalizedName = Normalizer::normalizeName($displayName);
            $version = $metadata['version'] ?? 'unknown';
            
            return [
                'status' => 'duplicate',
                'message' => 'File already exists',
                'project' => $normalizedName,
                'version' => $version,
            ];
        }

        $metadata = MetadataParser::parse($tmpPath, $filename);
        if (!$metadata['name']) {
            throw new \RuntimeException("Could not extract package name");
        }

        $displayName = $metadata['name'];
        $normalizedName = Normalizer::normalizeName($displayName);
        $version = $metadata['version'] ?? 'unknown';

        $projectId = $this->storage->saveProject($normalizedName, $displayName);

        $storagePath = "packages/{$normalizedName}/{$version}/{$filename}";
        $fullPath = $this->config->get('STORAGE_ROOT') . '/' . $storagePath;
        
        if (!is_dir(dirname($fullPath))) mkdir(dirname($fullPath), 0755, true);
        if (!rename($tmpPath, $fullPath) && !copy($tmpPath, $fullPath)) {
            throw new \RuntimeException("Failed to move file");
        }

        $fileData = [
            'filename' => $filename,
            'version' => $version,
            'storage_path' => $storagePath,
            'size_bytes' => $size,
            'sha256' => $sha256,
            'source_url' => $sourceUrl,
            'status' => 'accepted',
            'requires_python' => $metadata['requires_python'],
            'metadata_verified' => $metadata['verified'],
            'accepted_at' => date('Y-m-d H:i:s'),
        ];

        $this->storage->addFile($projectId, $fileData);
        $this->regenerateStaticFiles();

        return [
            'status' => 'accepted',
            'message' => 'File ingested',
            'project' => $normalizedName,
            'version' => $version,
        ];
    }

    private function verifyRecordSignature(string $filepath, string $filename): bool
    {
        $publicKeyPath = "/var/www/html/keys/repo-signing.pub";
        if (!file_exists($publicKeyPath)) {
            throw new \RuntimeException("Public key not found at: {$publicKeyPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new \RuntimeException("Only whl files are accepted");
        }

        $recordContent = null;
        $recordSigContent = null;
        $recordPath = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'];

            if (preg_match('/\.dist-info\/RECORD$/i', $name)) {
                $recordPath = $name;
                $recordContent = $zip->getFromIndex($i);
            }
            if (preg_match('/\.dist-info\/RECORD\.sig$/i', $name)) {
                $recordSigContent = $zip->getFromIndex($i);
            }
        }

        $zip->close();

        if ($recordContent === null || $recordSigContent === null) {
            return false;
        }

        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        if ($publicKey === false) {
            return false;
        }

        $result = openssl_verify($recordContent, $recordSigContent, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function regenerateStaticFiles(): void
    {
        $generator = new \App\Services\IndexGenerator();
        $generator->regenerateAll();

        $manifest = new \App\Services\ManifestService();
        $manifest->generate();
    }
}