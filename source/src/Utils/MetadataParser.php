<?php

namespace App\Utils;

use ZipArchive;

class MetadataParser
{
    public static function parse(string $filepath, string $filename): array
    {
        if (preg_match('/\.whl$/i', $filename)) {
            return self::parseWheel($filepath, $filename);
        }

        return ['name' => null, 'version' => null, 'requires_python' => null, 'verified' => false];
    }

    private static function parseWheel(string $filepath, string $filename): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            return self::fallbackToFilename($filename);
        }

        $metadataContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (preg_match('/\.dist-info\/METADATA$/i', $stat['name'])) {
                $metadataContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        if ($metadataContent === null) {
            return self::fallbackToFilename($filename);
        }

        return self::parseMetadataContent($metadataContent);
    }

    private static function parseSdist(string $filepath, string $filename): array
    {
        return self::fallbackToFilename($filename);
    }

    private static function parseMetadataContent(string $content): array
    {
        $result = ['name' => null, 'version' => null, 'requires_python' => null, 'verified' => true];

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('/^Name:\s*(.+)$/i', $line, $matches)) {
                $result['name'] = trim($matches[1]);
            } elseif (preg_match('/^Version:\s*(.+)$/i', $line, $matches)) {
                $result['version'] = trim($matches[1]);
            } elseif (preg_match('/^Requires-Python:\s*(.+)$/i', $line, $matches)) {
                $result['requires_python'] = trim($matches[1]);
            }
        }

        return $result;
    }

    private static function fallbackToFilename(string $filename): array
    {
        // $basename = preg_replace('/\.(whl|tar\.gz)$/i', '', $filename);
        $basename = preg_replace('/\.whl$/i', '', $filename);
        $parts = explode('-', $basename);

        return [
            'name' => $parts[0] ?? null,
            'version' => $parts[1] ?? null,
            'requires_python' => null,
            'verified' => false,
        ];
    }
}
