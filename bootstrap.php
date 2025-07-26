<?php

use NixPHP\Queue\Commands\QueueWorkerCommand;
use NixPHP\Queue\Core\Queue;
use NixPHP\Queue\Core\Drivers\SQLiteQueue;
use function NixPHP\app;
use function NixPHP\Database\database;

command()->add(QueueWorkerCommand::class);

app()->container()->set('queue', function() {
    return new Queue(new SQLiteQueue(database()));
});
