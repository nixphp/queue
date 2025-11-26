<?php

declare(strict_types=1);

namespace NixPHP\Queue\Core;

use function NixPHP\app;

class Queue
{

    protected QueueDriverInterface $driver;

    public function __construct(QueueDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function push(string $class, array $payload = []): void
    {
        $this->driver->enqueue($class, $payload);
    }

    public function pop(): ?array
    {
        return $this->driver->dequeue();
    }

    public function driver(): QueueDriverInterface
    {
        return $this->driver;
    }

    public function pushAndRun(string $class, array $payload = []): void
    {
        $this->push($class, $payload);

        $basePath = app()->getBasePath();
        $command = 'cd ' . $basePath . ' && ./vendor/bin/nix queue:worker --once';

        // Fire off a background PHP process to handle the next job
        exec($command . ' > /dev/null 2>&1 &');
    }

}