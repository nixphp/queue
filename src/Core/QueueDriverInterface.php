<?php

declare(strict_types=1);

namespace NixPHP\Queue\Core;

interface QueueDriverInterface
{
    public function enqueue(string $class, array $payload): void;
    public function dequeue(): ?array;

}