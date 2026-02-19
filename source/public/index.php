<?php

require_once __DIR__ . '/../vendor/autoload.php';

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

use App\Router;
use App\Controllers\HomeController;
use App\Controllers\ProjectController;
use App\Controllers\IngestionController;
use App\Controllers\SimpleController;
use App\Controllers\PackageController;
use App\Controllers\PipController;

$router = new Router();

$router->get('/', function() {
    (new HomeController())->index();
});

$router->get('/project/{name}', function($params) {
    (new ProjectController())->show($params);
});

$router->post('/ingest/upload', function() {
    (new IngestionController())->upload();
});

$router->post('/ingest/url', function() {
    (new IngestionController())->url();
});

$router->get('/simple/', function() {
    (new SimpleController())->index();
});

$router->get('/simple/{name}/', function($params) {
    (new SimpleController())->project($params);
});

$router->get('/packages/{project}/{version}/{filename}', function($params) {
    (new PackageController())->serve($params);
});

$router->get('/manifest/manifest.json', function() {
    (new PackageController())->manifest();
});

$router->get('/manifest/manifest.json.sig', function() {
    (new PackageController())->manifestSignature();
});

$router->get('/public-keys/repo-signing.pub', function() {
    (new PackageController())->publicKey();
});

$router->get('/pip/install', function() {
    (new PipController())->install();
});

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
}