# Upgrade to 4.0

This release adds support for PHP 8.4 and 8.5.

## BC BREAK: The package is now `fet/queue`

`slm/queue` is no longer maintained. Version 4.0 is published as `fet/queue`. Replace the package in your
`composer.json`:

```sh
composer remove slm/queue
composer require fet/queue:^4.0
```

The `SlmQueue\` namespace is unchanged, so no code changes are needed for the rename. `fet/queue` conflicts with
`slm/queue`, so packages that require `slm/queue` (such as the existing adapters) cannot be installed together with it.

## BC BREAK: Dropped support for PHP 7.4, 8.0 and 8.1

SlmQueue now supports the PHP versions that are [supported by the PHP project](https://www.php.net/supported-versions.php):
8.2, 8.3, 8.4 and 8.5.

## BC BREAK: Dropped the Laminas MVC integration

[Laminas MVC is retiring](https://getlaminas.org/blog/2026-03-06-laminas-mvc-eol-schedule.html): it is security-only
and will not support PHP 8.5. The Laminas project recommends moving to Mezzio, which SlmQueue keeps supporting through
`SlmQueue\ConfigProvider`. The following were removed:

- `SlmQueue\Module`. Register `SlmQueue\ConfigProvider` in your Mezzio `config/config.php` instead.
- The `queue` controller plugin (`SlmQueue\Controller\Plugin\QueuePlugin`) and its factory
  (`SlmQueue\Factory\QueueControllerPluginFactory`). Inject the queue from `SlmQueue\Queue\QueuePluginManager`
  into your request handler instead.
- `SlmQueue\Controller\Exception\QueueNotFoundException`, which was only thrown by the controller plugin.

## BC BREAK: Configuration uses the `dependencies` key

`config/module.config.php` now registers its services under `dependencies` (Mezzio) instead of `service_manager`.

## Dependencies

- `symfony/console` is now a direct requirement (`^6.4.8 || ^7.4 || ^8.1`), as `StartWorkerCommand` extends it.
- Minimum versions were raised to the first releases supporting PHP 8.5: `laminas/laminas-eventmanager` ^3.15,
  `laminas/laminas-servicemanager` ^3.24, `laminas/laminas-stdlib` ^3.21 and `laminas/laminas-cli` ^1.13.

# Upgrade to 3.0

This release adds support for PHP 8.0.

## BC BREAK: `laminas-cli` replaces `laminas-mvc-console`

The CLI can now be invoked with the following command:

```sh
vendor/bin/laminas slm-queue:start
```

## BC BREAK: Dropped support for PHP 7.3

SlmQueue now requires at least PHP 7.4.

## BC BREAK: Added `QueueInterface::getWorkerName()`

Classes implementing `QueueInterface` are now required to indicate which worker should be used to process the queue. This change will likely only affect you if you use a custom delegator for your queue.

## BC BREAK: Workers are now managed by `WorkerPluginManager`

Workers (and their aliases and delegators) should now be configured under `slm_queue.worker_manager` instead of `service_manager` (Laminas) or `dependencies` (Mezzio).
