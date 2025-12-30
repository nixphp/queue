<?php

declare(strict_types=1);

namespace NixPHP\Queue\Decorators\Drivers;

use NixPHP\Queue\Drivers\QueueDriverInterface;

interface ChannelQueueDriverInterface extends QueueDriverInterface
{
    public function enqueueTo(string $channel, string $class, array $payload): void;
    public function dequeueFrom(string $channel): ?array;
}
