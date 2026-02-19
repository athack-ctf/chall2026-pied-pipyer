<?php

namespace App\Controllers;

use App\Models\Storage;

class HomeController
{
    public function index(): void
    {
        $storage = new Storage();
        $recentFiles = $storage->getRecentFiles(20);
        require __DIR__ . '/../../views/home.php';
    }
}
