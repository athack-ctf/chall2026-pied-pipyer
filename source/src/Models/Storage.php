<?php

namespace App\Models;

use App\Database;
use PDO;

class Storage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getProjects(): array
    {
        $stmt = $this->db->query("
            SELECT p.* FROM projects p
            INNER JOIN files f ON f.project_id = p.id
            WHERE f.status = 'accepted'
            GROUP BY p.id
            ORDER BY p.normalized_name
        ");
        return $stmt->fetchAll();
    }

    public function getProject(string $normalizedName): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE normalized_name = ?");
        $stmt->execute([$normalizedName]);
        $project = $stmt->fetch();
        
        if (!$project) {
            return null;
        }

        $project['files'] = $this->getProjectFiles($project['id']);
        return $project;
    }

    public function saveProject(string $normalizedName, string $displayName): int
    {
        $stmt = $this->db->prepare("SELECT id FROM projects WHERE normalized_name = ?");
        $stmt->execute([$normalizedName]);
        $existing = $stmt->fetch();

        if ($existing) {
            return (int)$existing['id'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO projects (normalized_name, display_name, created_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$normalizedName, $displayName, date('Y-m-d H:i:s')]);
        return (int)$this->db->lastInsertId();
    }

    public function addFile(int $projectId, array $fileData): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO files (
                project_id, filename, version, storage_path, size_bytes, sha256,
                source_url, status, requires_python, metadata_verified, accepted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $projectId,
            $fileData['filename'],
            $fileData['version'],
            $fileData['storage_path'],
            $fileData['size_bytes'],
            $fileData['sha256'],
            $fileData['source_url'] ?? null,
            $fileData['status'],
            $fileData['requires_python'] ?? null,
            $fileData['metadata_verified'] ? 1 : 0,
            $fileData['accepted_at'],
        ]);
    }

    public function getAcceptedFiles(): array
    {
        $stmt = $this->db->query("
            SELECT f.*, p.normalized_name
            FROM files f
            INNER JOIN projects p ON f.project_id = p.id
            WHERE f.status = 'accepted'
            ORDER BY f.accepted_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function getRecentFiles(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, p.normalized_name, p.display_name
            FROM files f
            INNER JOIN projects p ON f.project_id = p.id
            WHERE f.status = 'accepted'
            ORDER BY f.accepted_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function fileExistsBySha256(string $sha256): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM files WHERE sha256 = ?");
        $stmt->execute([$sha256]);
        return $stmt->fetch() !== false;
    }

    private function getProjectFiles(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM files
            WHERE project_id = ? AND status = 'accepted'
            ORDER BY accepted_at DESC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
}
