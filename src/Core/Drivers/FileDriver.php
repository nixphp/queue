<?php

namespace NixPHP\Queue\Core\Drivers;

use NixPHP\Queue\Core\QueueDeadletterDriverInterface;
use NixPHP\Queue\Core\QueueDriverInterface;
use function NixPHP\app;
use function NixPHP\config;

class FileDriver implements QueueDriverInterface, QueueDeadletterDriverInterface
{

    public function enqueue(string $class, array $payload): void
    {
        $id = $payload['_job_id'] = $payload['_job_id'] ?? bin2hex(random_bytes(8));

        $basePath = app()->getBasePath() . '/storage/queue';
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $data = json_encode(['class' => $class, 'payload' => $payload]);

        file_put_contents($basePath .'/' . $id . '.job', $data, FILE_APPEND);
    }

    public function dequeue(): ?array
    {
        $basePath = app()->getBasePath() . '/storage/queue';
        $files = glob($basePath . '/*.job');
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

    public function deadletter(string $class, array $payload, \Throwable $exception): void
    {
        $id = $payload['_job_id'];

        $basePath = app()->getBasePath() . '/storage/queue/deadletter';
        $path = config('queue:deadletter_path', $basePath);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file = $path . '/' . $id . '.job';

        file_put_contents($file, json_encode([
            'id'        => $id,
            'class'     => $class,
            'payload'   => $payload,
            'error'     => $exception->getMessage(),
            'trace'     => $exception->getTraceAsString(),
            'failed_at' => date('c'),
        ], JSON_PRETTY_PRINT));
    }

    public function retryFailed(bool $keep = false): int
    {
        $basePath = app()->getBasePath() . '/storage/queue/deadletter';
        $deadDir = config('queue:deadletter_path', $basePath);
        if (!is_dir($deadDir)) {
            return 0;
        }

        $files = glob($deadDir . '/*.job');
        $count = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (!$data || !isset($data['class'], $data['payload'])) {
                continue;
            }

            $payload = $data['payload'];
            unset($payload['_attempts'], $payload['error'], $payload['trace'], $payload['failed_at']);

            $this->enqueue($data['class'], $payload);
            $count++;

            if (!$keep) {
                unlink($file);
            }
        }

        return $count;
    }

}