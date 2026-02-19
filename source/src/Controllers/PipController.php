<?php

namespace App\Controllers;

class PipController
{
    public function install(): void
    {
        try {
            $packageName = "hello-world-package";

            $env = [
                'PYTHONPATH' => '/usr/local/lib/python3.13/dist-packages'
            ];

            $cmd = [
                'pip',
                'install',
                '--upgrade',
                '--force-reinstall',
                '--target', '/usr/local/lib/python3.13/dist-packages',
                '--index-url', 'http://localhost/simple/',
                '--find-links', '/app/local-packages',
                '--no-cache-dir',
                $packageName
            ];

            $descriptorspec = [
                0 => ['pipe', 'r'],  
                1 => ['pipe', 'w'],  
                2 => ['pipe', 'w'],  
            ];

            $process = proc_open($cmd, $descriptorspec, $pipes);

            if (!is_resource($process)) {
                throw new \RuntimeException("Failed to execute pip command");
            }

            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);

            echo json_encode([
                'success' => $returnCode === 0,
                'package' => $packageName,
                'stdout' => $stdout,
                'stderr' => $stderr,
                'return_code' => $returnCode,
                'command' => implode(' ', $cmd),
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'stdout' => '',
                'stderr' => $e->getMessage(),
                'return_code' => null,
            ]);
        }
    }
}