<?php

declare(strict_types=1);

namespace NixPHP\Queue\Core;

interface QueueDeadletterDriverInterface
{

    public function deadletter(string $class, array $payload, \Throwable $exception): void;

    public function retryFailed(bool $keep = false): int;

}