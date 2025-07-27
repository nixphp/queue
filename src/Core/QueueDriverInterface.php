<?php

namespace NixPHP\Queue\Core;

interface QueueDriverInterface
{
    public function enqueue(string $class, array $payload): void;
    public function dequeue(): ?array;

    public function deadletter(string $class, array $payload, \Throwable $exception): void;

}