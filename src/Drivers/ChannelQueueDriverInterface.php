<?php

declare(strict_types=1);

namespace NixPHP\Queue\Drivers;

interface ChannelQueueDriverInterface extends QueueDriverInterface
{
    public function enqueueTo(string $channel, string $class, array $payload): void;
    public function dequeueFrom(string $channel): ?array;
}
