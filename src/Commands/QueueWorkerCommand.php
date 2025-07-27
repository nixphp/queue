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
            ->setTitle('NixPHP Queue Worker')
            ->setDescription('Run the queue worker')
            ->addOption('once');
    }

    public function run(Input $input, Output $output): int
    {
        if ($input->getOption('help')) {
            $this->showHelp($output);
            return self::SUCCESS;
        }

        $once = $input->getOption('once');

        do {
            ob_flush();

            $jobData = queue()->pop();

            if (!$jobData) {
                if ($once) return static::SUCCESS;
                sleep(5);
                continue;
            }

            $class = $jobData['class'];
            $payload = $jobData['payload'];

            if (!class_exists($class)) {
                $output->writeLine("⚠️ Job class $class not found.\n");
                continue;
            }

            $job = new $class($payload);

            if (! ($job instanceof QueueJobInterface)) {
                $output->writeLine("❌ Job $class does not implement QueueJobInterface.");
            }

            $start = microtime(true);
            $output->writeLine("🚨 Job $class started at " . date('d.m.Y H:i:s:v', time()) . ".");
            $output->writeEmptyLine();
            $job->handle($output);
            $output->writeEmptyLine();
            $output->writeLine( "✅ Job $class processed in " . number_format(microtime(true) - $start, 5) . " seconds.\n");
            $output->writeLine('---');
            $output->writeEmptyLine();

            ob_flush();

            if ($once) return static::SUCCESS;

        } while (true);
    }
}
