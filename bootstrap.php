<?php

use NixPHP\Queue\Commands\QueueWorkerCommand;
use NixPHP\Queue\Core\Drivers\FileQueue;
use NixPHP\Queue\Core\Queue;
use function NixPHP\app;

command()->add(QueueWorkerCommand::class);

app()->container()->set('queue', function() {
    return new Queue(new FileQueue());
});
