<?php

declare(strict_types=1);

use NixPHP\Queue\Commands\QueueRetryFailedCommand;
use NixPHP\Queue\Commands\QueueWorkerCommand;
use NixPHP\Queue\Core\Drivers\FileDriver;
use NixPHP\Queue\Core\Queue;
use function NixPHP\app;

command()->add(QueueWorkerCommand::class);
command()->add(QueueRetryFailedCommand::class);

app()->container()->set(Queue::class, function() {
    $qPath = app()->getBasePath() . '/storage/queue';
    $dPath = app()->getBasePath() . '/storage/queue/deadletter';
    return new Queue(new FileDriver($qPath, $dPath));
});
