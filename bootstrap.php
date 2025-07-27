<?php

use NixPHP\Queue\Commands\QueueRetryFailedCommand;
use NixPHP\Queue\Commands\QueueWorkerCommand;
use NixPHP\Queue\Core\Drivers\FileDriver;
use NixPHP\Queue\Core\Queue;
use function NixPHP\app;

command()->add(QueueWorkerCommand::class);
command()->add(QueueRetryFailedCommand::class);

app()->container()->set('queue', function() {
    return new Queue(new FileDriver());
});
