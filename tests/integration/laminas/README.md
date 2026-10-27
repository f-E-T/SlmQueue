This is a sample Laminas MVC project, that we set-up for testing purposes. It is configured to be able to run a single
job, and it contains the tests for the `queue` controller plugin.

Laminas MVC does not support PHP 8.5, so these tests only run on PHP 8.2 to 8.4.

To run:

```sh
composer test
```

Note that `laminas-cli` will bootstrap the application automatically when `vendor/bin/laminas` is executed in this
directory. That is because it will find the `config/container.php` file.
