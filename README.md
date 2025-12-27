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
* ✅ Logical **channels** (single queue, multiple job streams)
* ✅ One-off async execution (`pushAndRun()`)
* ✅ Deadletter handling **per channel**
* ✅ Retry support (channel-aware)
* ✅ Fully PSR-4 and event-loop friendly
* ✅ Extendable: write your own driver for SQLite, Redis, etc.

---

## 📥 Installation

```bash
composer require nixphp/queue
````

That’s it. The plugin will be autoloaded automatically.

---

## 🚀 Usage

### ➕ Queue a job (default channel)

Create a job class that implements the `QueueJobInterface`:

```php
use NixPHP\Queue\QueueJobInterface;

class SendWelcomeEmail implements QueueJobInterface
{
    public function __construct(protected array $payload) {}

    public function execute(): void
    {
        // Send your email here
    }
}
```

Push it to the default queue:

```php
queue()->push(SendWelcomeEmail::class, ['email' => 'user@example.com']);
```

---

### 🧵 Using channels

Channels are **logical job streams** inside the same queue backend.
They allow you to separate workloads (e.g. `emails`, `mcp_out`, `notifications`)
without running multiple queue systems.

Push a job to a specific channel:

```php
queue('emails')->push(SendWelcomeEmail::class, [
    'email' => 'user@example.com'
]);
```

Internally, channels are handled by the queue driver.
The `Queue` API itself remains unchanged.

---

### ⚡ Fire-and-forget (async)

For **one-off asynchronous execution**, use:

```php
queue('emails')->pushAndRun(
    SendWelcomeEmail::class,
    ['email' => 'user@example.com']
);
```

This queues the job and immediately runs it in the background via a short-lived CLI process,
automatically passing the channel to the worker.

Ideal for emails, logging, notifications, or side-effects that should not block a request.

---

## 🧵 Start the worker

Run the worker and listen on the default channel:

```bash
./bin/nix queue:worker
```

Listen on a specific channel:

```bash
./bin/nix queue:worker --channel=emails
```

Listen on multiple channels (checked in order):

```bash
./bin/nix queue:worker --channels=default,emails,mcp_out
```

Run a single job only:

```bash
./bin/nix queue:worker --once
```

> 🔹 `--once` is also used internally by `pushAndRun()`.

---

## 💥 Deadletter & Retry (channel-aware)

If a job fails too often, it is written to a **deadletter directory per channel**:

```
storage/queue/deadletter/<channel>/<job-id>.job
```

Retry failed jobs for the default channel:

```bash
./bin/nix queue:retry-failed
```

Retry failed jobs for a specific channel:

```bash
./bin/nix queue:retry-failed --channel=emails
```

By default, retried jobs are removed from the deadletter queue.
Use `--keep` to retain them:

```bash
./bin/nix queue:retry-failed --channel=emails --keep
```

---

## 🧠 Drivers

The queue system is driver-based.
Included drivers:

| Driver       | Description                            | Suitable for            |
| ------------ | -------------------------------------- | ----------------------- |
| `FileDriver` | Stores jobs as `.job` files in folders | Local use, no DB needed |
| *(planned)*  | SQLite / Redis / others                | Larger or shared setups |

To register a custom driver, configure it in your `bootstrap.php`:

```php
use NixPHP\Queue\Core\Queue;
use NixPHP\Queue\Drivers\FileDriver;

app()->container()->set(Queue::class, function () {
    return new Queue(
        new FileDriver(
            app()->getBasePath() . FileDriver::DEFAULT_QUEUE_PATH,
            app()->getBasePath() . FileDriver::DEFAULT_DEADLETTER_PATH
        )
    );
});
```

> 📁 The file paths are only relevant for `FileDriver`.

---

## 🛠️ Supervisor example (optional)

To run the worker persistently in production, use [Supervisor](http://supervisord.org):

```ini
[program:nixphp-worker]
command=php bin/nix queue:worker --channels=default,emails
directory=/path/to/your/app
autostart=true
autorestart=true
stderr_logfile=/var/log/nixphp/worker.err.log
stdout_logfile=/var/log/nixphp/worker.out.log
```

---

## ✅ Requirements

* `nixphp/framework` ^0.1.0
* `nixphp/cli` ^0.1.0 (required for worker commands)

---

## 📄 License

MIT License.
