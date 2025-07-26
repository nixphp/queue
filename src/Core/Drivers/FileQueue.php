<?php

namespace NixPHP\Queue\Core\Drivers;

use function NixPHP\app;

class FileQueue implements QueueDriverInterface
{

    public function enqueue(string $class, array $payload): void
    {
        $basePath = app()->getBasePath() . '/storage/queue';
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $data = json_encode(['class' => $class, 'payload' => $payload]);

        file_put_contents($basePath .'/' . time() . uniqid() . '.job', $data, FILE_APPEND);
    }

    public function dequeue(): ?array
    {
        $baseDir = app()->getBasePath() . '/storage/queue';
        $files = glob($baseDir . '/*.job');
        sort($files); // FIFO

        foreach ($files as $file) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);

            if ($data && isset($data['class'])) {
                unlink($file);
                return $data;
            }
        }

        return null;
    }


}