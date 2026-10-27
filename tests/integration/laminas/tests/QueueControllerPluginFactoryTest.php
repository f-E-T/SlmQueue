<?php

namespace SlmQueueMvcTest;

use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use SlmQueue\Controller\Plugin\QueuePlugin;
use SlmQueue\Factory\QueueControllerPluginFactory;
use SlmQueue\Job\JobPluginManager;
use SlmQueue\Queue\QueuePluginManager;

class QueueControllerPluginFactoryTest extends TestCase
{
    public function testCreateService(): void
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService(QueuePluginManager::class, new QueuePluginManager($serviceManager));
        $serviceManager->setService(JobPluginManager::class, new JobPluginManager($serviceManager));
        $factory = new QueueControllerPluginFactory();

        $queueControllerPluginFactory = $factory($serviceManager, QueuePlugin::class);
        static::assertInstanceOf(QueuePlugin::class, $queueControllerPluginFactory);
    }
}
