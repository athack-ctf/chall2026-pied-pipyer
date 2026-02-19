<?php

namespace App;

class Config
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->config = [
            'DB_PATH' => getenv('DB_PATH') ?: __DIR__ . '/../data/packages.db',
            'STORAGE_ROOT' => getenv('STORAGE_ROOT') ?: __DIR__ . '/../repo-data',
            'PRIVATE_KEY_PATH' => getenv('PRIVATE_KEY_PATH') ?: __DIR__ . '/../keys/repo-signing.key',
            'PUBLIC_KEY_PATH' => getenv('PUBLIC_KEY_PATH') ?: __DIR__ . '/../keys/repo-signing.pub',
            'MAX_IMPORT_SIZE_MB' => (int)(getenv('MAX_IMPORT_SIZE_MB') ?: 100),
            'IMPORT_TIMEOUT_SEC' => (int)(getenv('IMPORT_TIMEOUT_SEC') ?: 30),
            'IMPORT_MAX_REDIRECTS' => (int)(getenv('IMPORT_MAX_REDIRECTS') ?: 5),
        ];
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}
