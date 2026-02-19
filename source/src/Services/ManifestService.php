<?php

namespace App\Services;

use App\Config;
use App\Models\Storage;

class ManifestService
{
    private string $storageRoot;
    private string $privateKeyPath;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->storageRoot = $config->get('STORAGE_ROOT');
        $this->privateKeyPath = $config->get('PRIVATE_KEY_PATH');
    }

    public function generate(): void
    {
        $storage = new Storage();
        $files = $storage->getAcceptedFiles();

        $manifest = [
            'version' => '1.0',
            'generated_at' => date('c'),
            'files' => [],
        ];

        foreach ($files as $file) {
            $manifest['files'][] = [
                'project' => $file['normalized_name'],
                'version' => $file['version'],
                'filename' => $file['filename'],
                'sha256' => $file['sha256'],
                'size' => $file['size_bytes'],
                'accepted_at' => $file['accepted_at'],
                'source' => $file['source_url'] ? 'url' : 'upload',
                'requires_python' => $file['requires_python'],
                'metadata_verified' => (bool)$file['metadata_verified'],
            ];
        }

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $manifestPath = $this->storageRoot . '/manifest/manifest.json';
        if (!is_dir(dirname($manifestPath))) mkdir(dirname($manifestPath), 0755, true);
        file_put_contents($manifestPath, $manifestJson);

        $this->signManifest($manifestJson, $manifestPath . '.sig');
    }

    private function signManifest(string $data, string $signaturePath): void
    {
        if (!file_exists($this->privateKeyPath)) {
            return;
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));
        if ($privateKey === false) {
            return;
        }

        $signature = '';
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        file_put_contents($signaturePath, $signature);
    }
}
