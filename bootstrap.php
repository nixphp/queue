<?php

declare(strict_types=1);

use NixPHP\Queue\Commands\QueueRetryFailedCommand;
use NixPHP\Queue\Commands\QueueWorkerCommand;
use NixPHP\Queue\Core\Queue;
use NixPHP\Queue\Drivers\FileDriver;
use function NixPHP\app;
use function NixPHP\CLI\command;
use function NixPHP\guard;

command()->add(QueueWorkerCommand::class);
command()->add(QueueRetryFailedCommand::class);

app()->container()->set(Queue::class, function() {
    $qPath = app()->getBasePath() . FileDriver::DEFAULT_QUEUE_PATH;
    $dPath = app()->getBasePath() . FileDriver::DEFAULT_DEADLETTER_PATH;
    return new Queue(new FileDriver($qPath, $dPath));
});

if (!guard()->has('safePath')) {
    guard()->register('safePath', function ($path) {
        if (
            $path === '' ||
            str_contains($path, '..') ||
            str_starts_with($path, '/') ||
            str_contains($path, '://') ||
            !preg_match('/^[A-Za-z0-9_\/.-]+$/', $path)
        ) {
            throw new \InvalidArgumentException('Insecure path detected! Navigation outside of application root is not allowed.');
        }

        return $path;
    });
}
