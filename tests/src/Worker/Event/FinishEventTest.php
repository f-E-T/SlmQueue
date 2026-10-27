<?php

namespace SlmQueueTest\Worker\Event;

use PHPUnit\Framework\TestCase;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Worker\Event\FinishEvent;
use SlmQueue\Worker\WorkerInterface;

class FinishEventTest extends TestCase
{
    protected $queue;
    protected $worker;
    protected $event;

    public function setUp(): void
    {
        $this->queue = $this->createStub(QueueInterface::class);
        $this->worker = $this->createStub(WorkerInterface::class);
        $this->event = new FinishEvent($this->worker, $this->queue);
    }

    public function testSetsWorkerAsTarget()
    {
        static::assertEquals($this->worker, $this->event->getWorker());
    }

    public function testWorkerEventGetsQueue()
    {
        static::assertEquals($this->queue, $this->event->getQueue());
    }
}
