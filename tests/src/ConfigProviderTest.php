<?php

namespace SlmQueueTest;

use PHPUnit\Framework\TestCase;
use SlmQueue\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    public function testConfigProviderGetConfig(): void
    {
        $configProvider = new ConfigProvider();
        $config = $configProvider();

        static::assertNotEmpty($config['dependencies']);
        static::assertNotEmpty($config['slm_queue']);
        static::assertNotEmpty($config['laminas-cli']);
    }
}
