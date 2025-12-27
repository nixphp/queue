<?php

declare(strict_types=1);

namespace NixPHP\Queue\Commands;

use NixPHP\CLI\Core\AbstractCommand;
use NixPHP\CLI\Core\Input;
use NixPHP\CLI\Core\Output;
use NixPHP\Queue\Core\QueueJobInterface;
use NixPHP\Queue\Drivers\ChannelDeadletterDriverInterface;
use NixPHP\Queue\Drivers\QueueDeadletterDriverInterface;
use Throwable;
use function NixPHP\app;
use function NixPHP\config;
use function NixPHP\log;
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
            ->addOption('once')
            ->addOption('channel', null, true)
            ->addOption('channels', null, true)
            ->addOption('max-jobs', null, true)
            ->addOption('max-runtime', null, true);
    }

    public function run(Input $input, Output $output): int
    {
        if ($input->getOption('help')) {
            $this->showHelp($output);

            return self::SUCCESS;
        }

        $jobCount    = 0;
        $maxJobs     = $input->getOption('max-jobs') ?? null;
        $maxRuntime  = $input->getOption('max-runtime') ?? null;
        $timeStarted = time();

        $once     = $input->getOption('once');
        $channels = $this->resolveChannels($input);

        do {
            if (ob_get_level() > 0) {
                ob_flush();
            }

            if ($maxJobs && $jobCount >= $maxJobs) {
                $msg = 'NixPHP Queue Worker: Max jobs reached.';
                $output->writeLine('NixPHP Queue Worker: Quitting.');
                $output->writeLine($msg);
                log()->info($msg);
                break;
            }

            if ($maxRuntime && ($timeStarted + $maxRuntime) >= time()) {
                $msg = 'NixPHP Queue Worker: Max runtime reached.';
                $output->writeLine($msg);
                $output->writeLine('NixPHP Queue Worker: Quitting.');
                log()->info($msg);
                break;
            }

            [$jobData, $channelUsed] = $this->popFromChannels($channels);

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
                $output->writeLine("⚠ Job class $class not found.");
                continue;
            }

            $q = queue($channelUsed);

            try {
                $attempts++;

                $job = app()->container()->make($class, $payload);

                if (!($job instanceof QueueJobInterface)) {
                    throw new \RuntimeException("$class does not implement QueueJobInterface.");
                }

                $date = date('Y-m-d H:i:s');

                $output->writeLine("🕛 Job $class started at $date (attempt $attempts)...");

                $start = microtime(true);
                $job->execute($output);
                $output->writeEmptyLine();
                $output->writeLine("✔ Job $class done in " . number_format(microtime(true) - $start, 5) . "s.");

            } catch (Throwable $e) {
                $output->writeLine("⚠ Job $class failed: {$e->getMessage()} (attempt $attempts)");

                if ($attempts >= config('queue:max_attempts', 3)) {
                    $driver = $q->driver();

                    if ($driver instanceof QueueDeadletterDriverInterface) {
                        $driver->deadletter($class, $payload, $e);
                    }

                    $output->writeLine("❌ Giving up on $class after $attempts attempts.");
                    log()->error('NixPHP Worker: Error still persisted after ' . $attempts . ' attempts: ' . $e->getMessage());
                } else {
                    $payload['_attempts'] = $attempts;
                    sleep(config('queue:retry_delay', 5));
                    $q->push($class, $payload);
                    $output->writeLine("🔁 Retrying $class...");
                }
            }

            $output->writeLine('---');
            $output->writeEmptyLine();

            if ($once) return static::SUCCESS;

        } while (true);

        return static::SUCCESS;
    }

    /**
     * @return string[] ordered a list of channels to listen to
     */
    private function resolveChannels(Input $input): array
    {
        $channels = [];

        $single = $input->getOption('channel');
        if (is_string($single) && trim($single) !== '') {
            $channels[] = trim($single);
        }

        $multi = $input->getOption('channels');
        if (is_string($multi) && trim($multi) !== '') {
            foreach (explode(',', $multi) as $ch) {
                $ch = trim($ch);
                if ($ch !== '') $channels[] = $ch;
            }
        }

        // Fallback
        if ($channels === []) {
            $channels[] = 'default';
        }

        // Deduplicate while preserving order
        return array_values(array_unique($channels));
    }

    /**
     * @param string[] $channels
     * @return array{0:?array,1:string} [jobData, channelUsed]
     */
    private function popFromChannels(array $channels): array
    {
        foreach ($channels as $ch) {
            $job = queue($ch)->pop();
            if ($job) {
                return [$job, $ch];
            }
        }

        // none found -> return null and first channel for consistency
        return [null, $channels[0] ?? 'default'];
    }
}
