<div align="center" style="text-align: center;">

![Logo](https://nixphp.github.io/docs/assets/nixphp-logo-small-square.png)

[![NixPHP Queue Plugin](https://github.com/nixphp/queue/actions/workflows/php.yml/badge.svg)](https://github.com/nixphp/queue/actions/workflows/php.yml)

</div>

[← Back to NixPHP](https://github.com/nixphp/framework)

---

# nixphp/queue

> **Minimalistic queueing for NixPHP - file-based, simple, and extendable.**

This plugin provides a lightweight job queue system with CLI worker support
and no external dependencies by default.

> 🧩 Part of the official NixPHP plugin collection.
> Use it when you want to delay tasks, run background jobs, or decouple logic - without setting up Redis or RabbitMQ.

---

## 📦 Features

* ✅ File-based queue driver (no DB or Redis required)
* ✅ CLI worker for background processing
* ✅ Fully PSR-4 and event-loop friendly
* ✅ Extendable: write your own driver for SQLite, Redis, etc.
* ✅ Supports job classes with `handle()` method

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

### 🧵 Start the worker

Run the CLI worker to process jobs:

```bash
php bin/nix queue:work false
```

You can also process a single job only:

```bash
php bin/nix queue:work true
```

---

## 🧠 Drivers

The queue system is driver-based.
Included drivers:

| Driver        | Description                             | Suitable for                  |
| ------------- | --------------------------------------- | ----------------------------- |
| `FileQueue`   | Stores jobs as `.job` files in a folder | Local use, no DB needed       |
| `SQLiteQueue` | Stores jobs in SQLite table             | Shared memory across requests |

To change the driver, register it in your plugin manually:

```php
use NixPHP\Queue\Queue;
use NixPHP\Queue\Drivers\FileQueue;

app()->set('queue', fn() => new Queue(
    new FileQueue(__DIR__ . '/../storage/queue')
));
```

---

## 🛠️ Supervisor example (optional)

To run the queue worker persistently, use Supervisor:

```ini
[program:nixphp-worker]
command=php nix queue:work
directory=/path/to/your/app
autostart=true
autorestart=true
stderr_logfile=/var/log/nixphp/worker.err.log
stdout_logfile=/var/log/nixphp/worker.out.log
```

---

## ✅ Requirements

* `nixphp/framework` >= 1.0
* `nixphp/cli` (required for worker command)

---

## 📄 License

MIT License.