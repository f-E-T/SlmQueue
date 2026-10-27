SlmQueue
========

[![Latest Stable Version](https://poser.pugx.org/fet/queue/v/stable.png)](https://packagist.org/packages/fet/queue)

This is a maintained fork of [Webador/SlmQueue](https://github.com/Webador/SlmQueue) (`slm/queue`), which is no longer
maintained. It is published as `fet/queue`, starting at version 4.0, and supports the
[PHP versions that are currently supported](https://www.php.net/supported-versions.php). The `SlmQueue\` namespace is
unchanged, so `fet/queue` cannot be installed next to `slm/queue`. See [UPGRADE.md](UPGRADE.md) when coming from
`slm/queue` 3.x.

SlmQueue is a job queue abstraction layer for Laminas applications, such as Mezzio, that use
[laminas-servicemanager](https://github.com/laminas/laminas-servicemanager). The Laminas MVC integration was removed in
4.0. It supports various job queue systems and
makes your application independent from the underlying system you use. Each queue system needs an adapter that
implements `SlmQueue\Queue\QueueInterface`.

The adapters written for `slm/queue` ([Doctrine ORM](https://github.com/Webador/SlmQueueDoctrine),
[RabbitMQ](https://github.com/rnd-cosoft/slm-queue-rabbitmq), [Amazon SQS](https://github.com/Webador/SlmQueueSqs))
require `slm/queue` and PHP 8.3 or older, so they cannot be installed together with `fet/queue`.

When to use
-----------

A job queue helps to offload long or memory-intensive processes from the HTTP requests clients sent to the Laminas
application. This will make your response times shorter and your visitors happier. There are many use cases
for asynchronous jobs and a few examples are:

1. Send an email
2. Create a PDF file
3. Connect to a third party server over HTTP

In all cases you want to serve the response as soon as possible to your visitor, without letting them wait for this
long process. SlmQueue enables you to implement a job queue system very easily within your existing application.

Installation
------------

SlmQueue works with [Composer](http://getcomposer.org). Make sure you have the composer.phar downloaded and you have a
`composer.json` file at the root of your project. To install it, add the following line into your `composer.json` file:

```json
"require": {
    "fet/queue": "^4.0"
}
```

After installation of the package, you need to complete the following steps to use SlmQueue:

 1. Register `SlmQueue\ConfigProvider` in your application's configuration (in Mezzio: `config/config.php`). If you use
[laminas-component-installer](https://github.com/laminas/laminas-component-installer), this happens automatically.
 2. Copy the `slm_queue.global.php.dist` (you can find this file in the `config` folder of SlmQueue) into
your `config/autoload` folder and apply any setting you want.

NB. SlmQueue is a skeleton and therefore useless by itself. You need an adapter (an implementation of
`SlmQueue\Queue\QueueInterface`, usually extending `SlmQueue\Queue\AbstractQueue`) for your queue system to push jobs
into the queue.

Requirements
------------
* PHP 8.2, 8.3, 8.4 or 8.5
* [laminas-servicemanager ^3.24](https://github.com/laminas/laminas-servicemanager)

SlmQueue 4 dropped the Laminas MVC integration (the `SlmQueue` module and the `queue` controller plugin), because
[Laminas MVC is retiring](https://getlaminas.org/blog/2026-03-06-laminas-mvc-eol-schedule.html) and will not support
PHP 8.5. See [UPGRADE.md](UPGRADE.md).


Code samples
------------
Below are a few snippets which show the power of SlmQueue in your application. The full documentation is available in
[docs/](/docs) directory.

A sample job to send an email with php's `mail()` might look like this:

```php
namespace MyModule\Job;

use SlmQueue\Job\AbstractJob;

class EmailJob extends AbstractJob
{
    public static function create(string $to, string $subject, string $message): self
    {
        // This will bypass the constructor, and thus load a job without
        // having to load the dependencies.
        $job = self::createEmptyJob([
            'subject' => $subject,
            'to' => $to,
            'message' => $message,
        ]);

        // Add some metadata, so we see what is going on.
        $job->setMetadata('to', $to);

        return $job;
    }

    private SomeMailService $mailService;

    public function __construct(SomeMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    public function execute()
    {
        $payload = $this->getContent();

        $to      = $payload['to'];
        $subject = $payload['subject'];
        $message = $payload['message'];

        $this->mailService->send($to, $subject, $message);
    }
}
```

If you want to inject this job into a queue, you can do this for instance in your request handler:

```php
namespace MyModule\Handler;

use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use MyModule\Job\EmailJob;
use SlmQueue\Queue\QueueInterface;

class MyHandler implements RequestHandlerInterface
{
    public function __construct(private QueueInterface $queue)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Do some work

        $this->queue->push(
            EmailJob::create('john@doe.com', 'Just hi', 'Hi, I want to say hi!'),
            ['delay' => 60]
        );

        return new EmptyResponse();
    }
}
```

Now the above code lets you insert jobs in a queue, but then you need to spin up a worker which can process these jobs.

SlmQueue integrates with [`laminas-cli`](https://github.com/laminas/laminas-cli) for command line usage. You can start up a worker for queue "default" with the following command:

```sh
$ vendor/bin/laminas slm-queue:start default
```

Contributing
------------

SlmQueue is developed by various fanatic Laminas users. The code is written to be as generic as possible for
Laminas applications. If you want to contribute to SlmQueue, fork this repository and start hacking!

Any bugs can be reported as an [issue](https://github.com/f-E-T/SlmQueue/issues) at GitHub. If you want to
contribute, please be aware of the following guidelines:

 1. Fork the project to your own repository
 2. Use branches to work on your own part
 3. Create a Pull Request at the canonical SlmQueue repository
 4. Make sure to cover changes with the right amount of unit tests
 5. If you add a new feature, please work on some documentation as well

For long-term contributors, push access to this repository is granted.

Who to thank?
-------------

[Jurian Sluiman](https://github.com/juriansluiman) and [Michaël Gallego](https://github.com/bakura10) did the initial work on creating this repo, and maintained it for a long time.

Currently it is maintained by:

* [Bas Kamer](https://github.com/basz)
* [Roel van Duijnhoven](https://github.com/roelvanduijnhoven)
