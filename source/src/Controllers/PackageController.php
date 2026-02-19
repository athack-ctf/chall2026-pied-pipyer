<?php

namespace App\Controllers;

use App\Config;

class PackageController
{
    private string $storageRoot;

    public function __construct()
    {
        $this->storageRoot = Config::getInstance()->get('STORAGE_ROOT');
    }

    public function serve(array $params): void
    {
        $path = $this->storageRoot . '/packages/' . 
                $params['project'] . '/' . 
                $params['version'] . '/' . 
                $params['filename'];

        if (!file_exists($path)) {
            http_response_code(404);
            echo "File not found";
            return;
        }

        $contentType = preg_match('/\.whl$/i', $params['filename']) 
            ? 'application/zip' 
            : 'application/gzip';

        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename($params['filename']) . '"');
        readfile($path);
    }

    public function manifest(): void
    {
        $path = $this->storageRoot . '/manifest/manifest.json';
        if (!file_exists($path)) {
            http_response_code(404);
            echo "Not found";
            return;
        }

        header('Content-Type: application/json');
        readfile($path);
    }

    public function manifestSignature(): void
    {
        $path = $this->storageRoot . '/manifest/manifest.json.sig';
        if (!file_exists($path)) {
            http_response_code(404);
            echo "Not found";
            return;
        }

        header('Content-Type: application/octet-stream');
        readfile($path);
    }

    public function publicKey(): void
    {
        $path = Config::getInstance()->get('PUBLIC_KEY_PATH');
        if (!file_exists($path)) {
            http_response_code(404);
            echo "Not found";
            return;
        }

        header('Content-Type: text/plain');
        readfile($path);
    }
}
