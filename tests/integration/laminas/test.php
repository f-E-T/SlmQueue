<?php

require 'vendor/autoload.php';

@unlink('temp/queue');
@unlink('temp/succesful');

// Bootstrap application, and initialize an empty database.
$application = Laminas\Mvc\Application::init(include 'config/application.config.php');
$serviceManager = $application->getServiceManager();

// Populate with a job, through the `queue` controller plugin that the SlmQueue module registers
$serviceManager
  ->get('ControllerPluginManager')
  ->get('queue')
  ->__invoke('default')
  ->pushJob(new \TestModule\TestJob());

// Run the queue for a single job
exec('vendor/bin/laminas slm-queue:start default', $output, $result);

// Assert that file was generated?
if (@file_get_contents('temp/succesful') !== 'YES') {
    echo 'Test was NOT successful.';
    exit(1);
}

echo 'Test was successful.';
exit(0);
