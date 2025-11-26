<div align="center" style="text-align: center;">

![Logo](https://nixphp.github.io/docs/assets/nixphp-logo-small-square.png)

[![NixPHP Queue Plugin](https://github.com/nixphp/queue/actions/workflows/php.yml/badge.svg)](https://github.com/nixphp/queue/actions/workflows/php.yml)

</div>

[← Back to NixPHP](https://github.com/nixphp/framework)

---

# nixphp/queue

> **Minimalistic queueing for NixPHP – file-based, simple, and extendable.**

This plugin provides a lightweight job queue system with CLI worker support and no external dependencies by default.

> 🧩 Part of the official NixPHP plugin collection.
> Use it when you want to delay tasks, run background jobs, or decouple logic – without setting up Redis or RabbitMQ.

---

## 📦 Features

* ✅ File-based queue driver (no DB or Redis required)
* ✅ CLI worker for background processing
* ✅ One-off async execution (`pushAndRun()`)
* ✅ Deadletter handling and retry support
* ✅ Fully PSR-4 and event-loop friendly
* ✅ Extendable: write your own driver for SQLite, Redis, etc.

---

## 📥 Installation

```bash
composer require nixphp/queue
```

That’s it. The plugin will be autoloaded automatically.

---

## 🚀 Usage

### ➕ Queue a job

Create a job class that implements the `QueueJobInterface`:

```php
use NixPHP\Queue\QueueJobInterface;

class SendWelcomeEmail implements QueueJobInterface
{
    public function __construct(protected array $payload) {}

    public function handle(): void
    {
        // Send your email here
    }
}
```

Push it to the queue:

```php
queue()->push(SendWelcomeEmail::class, ['email' => 'user@example.com']);
```

---

### ⚡ Fire-and-forget (async)

For **one-off asynchronous execution**, use:

```php
queue()->pushAndRun(SendWelcomeEmail::class, ['email' => 'user@example.com']);
```

This queues the job and immediately runs it in the background via a short-lived CLI process.

Great for use cases like emails, logging, or notifications - without blocking the current request.

---

### 🧵 Start the worker

Run the CLI worker to process jobs continuously:

```bash
./bin/nix queue:worker
```

Run a single job only:

```bash
./bin/nix queue:worker --once
```

> 🔹 `--once` is also used internally by `pushAndRun()` for async dispatching.

---

## 💥 Deadletter & Retry

Failed jobs are automatically written to the `deadletter` folder (only for `FileDriver`).

You can retry failed jobs via:

```bash
./bin/nix queue:retry-failed
```

By default, retried jobs are removed from the deadletter queue.
Use `--keep` to retain them:

```bash
./bin/nix queue:retry-failed --keep
```

---

## 🧠 Drivers

The queue system is driver-based.
Included drivers:

| Driver        | Description                             | Suitable for                  |
| ------------- | --------------------------------------- | ----------------------------- |
| `FileQueue`   | Stores jobs as `.job` files in a folder | Local use, no DB needed       |
| `SQLiteQueue` | Stores jobs in SQLite table             | Shared memory across requests |

To change the driver, register it manually:

```php
use NixPHP\Queue\Queue;
use NixPHP\Queue\Drivers\FileQueue;

app()->set('queue', fn() => new Queue(
    new FileQueue(__DIR__ . '/storage/queue', __DIR__ . '/storage/queue/deadletter')
));
```

> 📁 The file path is only relevant for `FileDriver`.

---

## 🛠️ Supervisor example (optional)

To run the worker persistently in production, use [Supervisor](http://supervisord.org):

```ini
[program:nixphp-worker]
command=php bin/nix queue:worker
directory=/path/to/your/app
autostart=true
autorestart=true
stderr_logfile=/var/log/nixphp/worker.err.log
stdout_logfile=/var/log/nixphp/worker.out.log
```

---

## ✅ Requirements

* `nixphp/framework` >= 0.1.0
* `nixphp/cli` >= 0.1.0 (required for worker commands)

---

## 📄 License

MIT License.