<?php

declare(strict_types=1);

namespace NixPHP\Queue;

use NixPHP\Queue\Core\Queue;
use NixPHP\Queue\Decorators\Drivers\ChannelDriver;
use NixPHP\Queue\Decorators\Drivers\ChannelQueueDriverInterface;
use function NixPHP\app;
use function NixPHP\log;

function queue(?string $channel = null): Queue
{
    $defaultQueue = app()->container()->get(Queue::class);

    if (empty($channel) || $channel === ChannelDriver::DEFAULT_CHANNEL) {
        return $defaultQueue;
    }

    $driver = $defaultQueue->driver();

    if (!$driver instanceof ChannelQueueDriverInterface) {
        log()->warning('Queue driver does not support channels.');
        return $defaultQueue;
    }

    return new Queue(new ChannelDriver($driver, $channel));
}