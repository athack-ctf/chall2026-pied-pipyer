<?php

namespace App\Services;

use App\Config;
use App\Models\Storage;

class IndexGenerator
{
    private string $storageRoot;
    private string $baseUrl;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->storageRoot = $config->get('STORAGE_ROOT');
    }

    public function regenerateAll(): void
    {
        $storage = new Storage();
        $projects = $storage->getProjects();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta name="pypi:repository-version" content="1.0">
    <title>Simple Index</title>
</head>
<body>
    <h1>Simple Index</h1>
';
        foreach ($projects as $project) {
            $name = $project['normalized_name'];
            $html .= "    <a href=\"{$name}/\">{$name}</a><br>\n";
        }
        $html .= '</body></html>';

        $indexPath = $this->storageRoot . '/simple/index.html';
        if (!is_dir(dirname($indexPath))) mkdir(dirname($indexPath), 0755, true);
        file_put_contents($indexPath, $html);

        foreach ($projects as $project) {
            $this->generateProjectIndex($project['normalized_name']);
        }
    }

    private function generateProjectIndex(string $name): void
    {
        $storage = new Storage();
        $project = $storage->getProject($name);
        
        if (!$project) return;

        $html = "<!DOCTYPE html>
<html>
<head>
    <meta name=\"pypi:repository-version\" content=\"1.0\">
    <title>Links for {$name}</title>
</head>
<body>
    <h1>Links for {$name}</h1>
"; 

        foreach ($project['files'] ?? [] as $file) {
            $url = '/packages/' . 
                   urlencode($name) . '/' . 
                   urlencode($file['version']) . '/' . 
                   urlencode($file['filename']);
            
            $sha256 = $file['sha256'];
            $requiresPython = $file['requires_python'] 
                ? ' data-requires-python="' . htmlspecialchars($file['requires_python']) . '"' 
                : '';

            $html .= "    <a href=\"{$url}#{$sha256}\"{$requiresPython}>{$file['filename']}</a><br>\n";
        }
        $html .= '</body></html>';

        $projectPath = $this->storageRoot . '/simple/' . $name . '/index.html';
        if (!is_dir(dirname($projectPath))) mkdir(dirname($projectPath), 0755, true);
        file_put_contents($projectPath, $html);
    }
}
