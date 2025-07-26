<?php

namespace NixPHP\Queue\Core\Drivers;

interface QueueDriverInterface
{
    public function enqueue(string $class, array $payload): void;
    public function dequeue(): ?array;
}