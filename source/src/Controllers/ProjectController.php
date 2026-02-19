<?php

namespace App\Controllers;

use App\Models\Storage;
use App\Utils\Normalizer;

class ProjectController
{
    public function show(array $params): void
    {
        $name = $params['name'] ?? '';
        $normalizedName = Normalizer::normalizeName($name);

        if (!Normalizer::isNormalized($name)) {
            header("Location: /project/{$normalizedName}", true, 301);
            exit;
        }

        $storage = new Storage();
        $project = $storage->getProject($normalizedName);

        if (!$project) {
            http_response_code(404);
            echo "Project not found";
            return;
        }

        require __DIR__ . '/../../views/project.php';
    }
}
