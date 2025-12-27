<?php

declare(strict_types=1);

namespace Tests\Unit;

use NixPHP\Queue\Drivers\FileDriver;
use PHPUnit\Framework\TestCase;

class FileDriverTest extends TestCase
{
    protected string $basePath;
    protected FileDriver $driver;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/nixphp-queue-test-' . uniqid();

        if (!is_dir($this->basePath . '/storage/queue')) {
            mkdir($this->basePath . '/storage/queue', 0777, true);
        }

        $this->driver = new FileDriver($this->basePath . '/storage/queue', $this->basePath . '/storage/queue/deadletter');
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->basePath);
    }

    protected function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testEnqueueAndDequeue(): void
    {
        $this->driver->enqueue('TestJob', ['foo' => 'bar']);
        $data = $this->driver->dequeue();

        $this->assertNotNull($data);
        $this->assertSame('TestJob', $data['class']);
        $this->assertSame('bar', $data['payload']['foo']);
        $this->assertArrayHasKey('_job_id', $data['payload']);
    }

    public function testDeadletter(): void
    {
        $payload = ['foo' => 'bar', '_job_id' => 'abc123'];
        $exception = new \RuntimeException('Something went wrong');

        $this->driver->deadletter('FailingJob', $payload, $exception);

        $file = $this->basePath . '/storage/queue/deadletter/default/abc123.job';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);

        $this->assertSame('FailingJob', $data['class']);
        $this->assertSame('Something went wrong', $data['error']);
    }

    public function testRetryFailed(): void
    {
        $payload = ['foo' => 'bar', '_job_id' => 'retryme'];
        $exception = new \RuntimeException('Nope');

        $this->driver->deadletter('RetryJob', $payload, $exception);

        $count = $this->driver->retryFailed();
        $this->assertSame(1, $count);

        $dequeued = $this->driver->dequeue();
        $this->assertSame('RetryJob', $dequeued['class']);
        $this->assertSame('bar', $dequeued['payload']['foo']);
        $this->assertArrayHasKey('_job_id', $dequeued['payload']);
        $this->assertSame('retryme', $dequeued['payload']['_job_id']);
    }

    public function testRetryKeepsFile(): void
    {
        $payload = ['foo' => 'bar', '_job_id' => 'keepme'];
        $this->driver->deadletter('KeepJob', $payload, new \Exception('fail'));

        $count = $this->driver->retryFailed(true);
        $this->assertSame(1, $count);

        $path = $this->basePath . '/storage/queue/deadletter/default/keepme.job';
        $this->assertFileExists($path);
    }
}
