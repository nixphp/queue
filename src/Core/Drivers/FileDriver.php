<?php

declare(strict_types=1);

namespace NixPHP\Queue\Core\Drivers;

use NixPHP\Queue\Core\QueueDeadletterDriverInterface;
use NixPHP\Queue\Core\QueueDriverInterface;
use Random\RandomException;
use Throwable;
use function NixPHP\app;
use function NixPHP\config;

class FileDriver implements QueueDriverInterface, QueueDeadletterDriverInterface
{
    public const string DEFAULT_QUEUE_PATH = '/storage/queue';
    public const string DEFAULT_DEADLETTER_PATH = '/storage/queue/deadletter';

    /**
     * @param string|null $queuePath
     * @param string|null $deadLetterPath
     */
    public function __construct(
        private readonly ?string $queuePath      = null,
        private readonly ?string $deadLetterPath = null
    ) {}

    /**
     * @param string $class
     * @param array  $payload
     *
     * @return void
     * @throws RandomException
     */
    public function enqueue(string $class, array $payload): void
    {
        $id          = $payload['_job_id'] ?? bin2hex(random_bytes(8));
        $defaultPath = app()->getBasePath() . self::DEFAULT_QUEUE_PATH;
        $basePath    = $this->queuePath ?? config('queue:path', $defaultPath);

        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $payload['_job_id'] = $id;
        $data = json_encode(['class' => $class, 'payload' => $payload]);

        file_put_contents(
            sprintf('%s/%s.job', $basePath, $id),
            $data
        );
    }

    /**
     * @return array|null
     */
    public function dequeue(): ?array
    {
        $defaultPath = app()->getBasePath() . self::DEFAULT_QUEUE_PATH;
        $basePath    = $this->queuePath ?? config('queue:path', $defaultPath);
        $files       = glob($basePath . '/*.job');

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

    /**
     * @param string     $class
     * @param array      $payload
     * @param Throwable  $exception
     *
     * @return void
     * @throws RandomException
     */
    public function deadletter(string $class, array $payload, \Throwable $exception): void
    {
        $id          = $payload['_job_id'] ?? 'rand_' . bin2hex(random_bytes(8));
        $defaultPath = app()->getBasePath() . self::DEFAULT_DEADLETTER_PATH;
        $path        = $this->deadLetterPath ?? config('queue:deadletterPath', $defaultPath);

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
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

    /**
     * @param bool $keep
     *
     * @return int
     * @throws RandomException
     */
    public function retryFailed(bool $keep = false): int
    {
        $defaultPath = app()->getBasePath() . self::DEFAULT_DEADLETTER_PATH;
        $path        = $this->deadLetterPath ?? config('queue:deadletterPath', $defaultPath);

        if (!is_dir($path)) {
            return 0;
        }

        $files = glob($path . '/*.job');
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