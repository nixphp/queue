<?php

namespace NixPHP\Queue\Core;

namespace NixPHP\Queue\Commands;

use NixPHP\Cli\Core\AbstractCommand;
use NixPHP\Cli\Core\Input;
use NixPHP\Cli\Core\Output;
use NixPHP\Queue\Core\QueueDeadletterDriverInterface;
use NixPHP\Queue\Core\QueueJobInterface;
use function NixPHP\config;
use function NixPHP\Queue\queue;

class QueueWorkerCommand extends AbstractCommand
{
    public const string NAME = 'queue:worker';

    private const int SLEEP_DELAY = 1;

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
                echo "Waiting for new job...\r";
                sleep(static::SLEEP_DELAY);
                continue;
            }

            $class    = $jobData['class'];
            $payload  = $jobData['payload'];
            $attempts = $payload['_attempts'] ?? 0;

            if (!class_exists($class)) {
                $output->writeLine("⚠️ Job class $class not found.");
                continue;
            }

            try {
                $attempts++;
                $job = new $class($payload, $output);

                if (!($job instanceof QueueJobInterface)) {
                    throw new \RuntimeException("$class does not implement QueueJobInterface.");
                }

                $date = date('Y-m-d H:i:s');

                $output->writeLine("🚨 Job $class started at $date (attempt $attempts)...");

                $start = microtime(true);
                $job->handle($output);
                $output->writeEmptyLine();
                $output->writeLine("✅ Job $class done in " . number_format(microtime(true) - $start, 5) . "s.");

            } catch (\Throwable $e) {

                $output->writeLine("⚠️ Job $class failed: {$e->getMessage()} (attempt $attempts)");

                if ($attempts >= config('queue:max_attempts', 3)) {

                    $driver = queue()->driver();

                    if ($driver instanceof QueueDeadletterDriverInterface) {
                        $driver->deadletter($class, $payload, $e);
                    }

                    $output->writeLine("❌ Giving up on $class after $attempts attempts.");
                    \NixPHP\log()->error('NixPHP Worker: Error still persisted after ' . $attempts . ' attempts: ' . $e->getMessage());

                } else {

                    $payload['_attempts'] = $attempts;
                    sleep(config('queue:retry_delay', 5));
                    queue()->push($class, $payload);
                    $output->writeLine("🔁 Retrying $class...");

                }

            }

            $output->writeLine('---');
            $output->writeEmptyLine();

            if ($once) return static::SUCCESS;

        } while (true);
    }
}
