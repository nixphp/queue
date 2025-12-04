<?php

declare(strict_types=1);

use NixPHP\Queue\Commands\QueueRetryFailedCommand;
use NixPHP\Queue\Commands\QueueWorkerCommand;
use NixPHP\Queue\Core\Queue;
use NixPHP\Queue\Drivers\FileDriver;
use function NixPHP\app;
use function NixPHP\CLI\command;

command()->add(QueueWorkerCommand::class);
command()->add(QueueRetryFailedCommand::class);

app()->container()->set(Queue::class, function() {
    $qPath = app()->getBasePath() . FileDriver::DEFAULT_QUEUE_PATH;
    $dPath = app()->getBasePath() . FileDriver::DEFAULT_DEADLETTER_PATH;
    return new Queue(new FileDriver($qPath, $dPath));
});
