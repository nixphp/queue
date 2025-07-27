<?php

namespace NixPHP\Queue\Core;

interface QueueDriverInterface
{
    public function enqueue(string $class, array $payload): void;
    public function dequeue(): ?array;

}