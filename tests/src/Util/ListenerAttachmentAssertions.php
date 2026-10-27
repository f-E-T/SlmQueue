<?php

namespace SlmQueueTest\Util;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;

trait ListenerAttachmentAssertions
{
    /**
     * Attaches the aggregate to a mocked event manager and asserts the listeners it attached, in order
     *
     * @param array<array{string, callable, int}> $expected
     */
    private function assertListenerAttachments(
        ListenerAggregateInterface $aggregate,
        int $priority,
        array $expected
    ): void {
        $attached = [];
        $eventManager = $this->createMock(EventManagerInterface::class);
        $eventManager->expects($this->exactly(count($expected)))
            ->method('attach')
            ->willReturnCallback(
                function (string $eventName, callable $listener, int $priority = 1) use (&$attached): callable {
                    $attached[] = [$eventName, $listener, $priority];

                    return $listener;
                }
            );

        $aggregate->attach($eventManager, $priority);

        static::assertSame($expected, $attached);
    }
}
