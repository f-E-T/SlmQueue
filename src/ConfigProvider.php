<?php

namespace SlmQueue;

class ConfigProvider
{
    public function __invoke(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
