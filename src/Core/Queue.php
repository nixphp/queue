<?php

namespace NixPHP\Queue\Core;

use NixPHP\Queue\Core\Drivers\QueueDriverInterface;

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

}