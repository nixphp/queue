<?php

namespace NixPHP\Queue\Commands;

use NixPHP\Cli\Core\AbstractCommand;
use NixPHP\Cli\Core\Input;
use NixPHP\Cli\Core\Output;
use NixPHP\Queue\Core\QueueDeadletterDriverInterface;

class QueueRetryFailedCommand extends AbstractCommand
{
    public const string NAME = 'queue:retry-failed';

    protected function configure(): void
    {
        $this->setTitle('NixPHP Queue Deadletter Retry')
            ->setDescription('Retry failed jobs')
            ->addOption('keep');
    }

    public function run(Input $input, Output $output): int
    {
        $driver = queue()->driver();

        if (! ($driver instanceof QueueDeadletterDriverInterface)) {
            $output->writeLine("❌ This driver does not support deadletter operations.");
            return static::ERROR;
        }

        ini_set('display_errors',1);

        $keep = $input->getOption('keep') ?? false;
        $count = $driver->retryFailed($keep);

        $output->writeLine("🔁 Retried $count failed job(s).");

        return static::SUCCESS;
    }

}