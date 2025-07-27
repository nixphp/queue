<?php

namespace NixPHP\Queue\Core;

interface QueueDeadletterDriverInterface
{

    public function deadletter(string $class, array $payload, \Throwable $exception): void;

    public function retryFailed(bool $keep = false): int;

}