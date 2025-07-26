<?php

namespace NixPHP\Queue\Core;

namespace NixPHP\Queue\Commands;

use NixPHP\Cli\Core\AbstractCommand;
use NixPHP\Cli\Core\Input;
use NixPHP\Cli\Core\Output;
use NixPHP\Queue\Core\QueueJobInterface;

class QueueWorkerCommand extends AbstractCommand
{
    public const string NAME = 'queue:worker';

    protected function configure(): void
    {
        $this
            ->setTitle('Queue worker')
            ->setDescription('Run the queue worker')
            ->addArgument('once');
    }

    public function run(Input $input, Output $output): int
    {
        $once = $input->getArgument('once');

        do {
            $jobData = queue()->pop();

            if (!$jobData) {
                if ($once) return static::SUCCESS;
                sleep(1);
                continue;
            }

            $class = $jobData['class'];
            $payload = $jobData['payload'];

            if (!class_exists($class)) {
                $output->writeLine("⚠️ Job class $class not found.\n");
                continue;
            }

            $job = new $class($payload);

            if ($job instanceof QueueJobInterface) {
                $job->handle();
                $output->writeLine( "✅ Job $class processed.\n");
            } else {
                $output->writeLine("❌ Job $class does not implement QueueJobInterface.\n");
            }

            if ($once) return static::SUCCESS;

        } while (true);
    }
}
