<?php

namespace App\Controllers;

use App\Config;
use App\Utils\Normalizer;

class SimpleController
{
    private string $storageRoot;

    public function __construct()
    {
        $this->storageRoot = Config::getInstance()->get('STORAGE_ROOT');
    }

    public function index(): void
    {
        $indexPath = $this->storageRoot . '/simple/index.html';
        if (!file_exists($indexPath)) {
            http_response_code(404);
            echo "Index not found";
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        readfile($indexPath);
    }

    public function project(array $params): void
    {
        $name = $params['name'] ?? '';
        $normalizedName = Normalizer::normalizeName($name);

        if (!Normalizer::isNormalized($name)) {
            header("Location: /simple/{$normalizedName}/", true, 301);
            exit;
        }

        $projectPath = $this->storageRoot . '/simple/' . $normalizedName . '/index.html';
        if (!file_exists($projectPath)) {
            http_response_code(404);
            echo "Project not found";
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        readfile($projectPath);
    }
}
