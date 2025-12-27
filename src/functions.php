<?php

declare(strict_types=1);

namespace NixPHP\Queue;

use NixPHP\Queue\Core\Queue;
use NixPHP\Queue\Drivers\ChannelDriver;
use NixPHP\Queue\Drivers\ChannelQueueDriverInterface;
use function NixPHP\app;

function queue(?string $channel = null): Queue
{
    $defaultQueue = app()->container()->get(Queue::class);

    if (empty($channel) || $channel === ChannelDriver::DEFAULT_CHANNEL) {
        return $defaultQueue;
    }

    $driver = $defaultQueue->getDriver();

    if ($driver instanceof ChannelQueueDriverInterface) {
        throw new \RuntimeException('Queue driver does not support channels.');
    }

    return new Queue(new ChannelDriver($driver, $channel));
}