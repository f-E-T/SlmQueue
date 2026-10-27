<?php

namespace SlmQueue;

/**
 * Laminas MVC module. Laminas MVC does not support PHP 8.5, so this only works on PHP 8.2 to 8.4.
 *
 * @deprecated Laminas MVC is end of life on 31 December 2028. Use SlmQueue\ConfigProvider (e.g. with Mezzio) instead.
 */
class Module
{
    public function getConfig(): array
    {
        $config = (new ConfigProvider())();
        $config['service_manager'] = $config['dependencies'];
        unset($config['dependencies']);

        return $config;
    }
}
