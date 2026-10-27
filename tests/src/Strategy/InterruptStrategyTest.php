<?php

namespace SlmQueueTest\Listener\Strategy;

use PHPUnit\Framework\TestCase;
use SlmQueue\Queue\QueueInterface;
use SlmQueue\Strategy\AbstractStrategy;
use SlmQueue\Strategy\InterruptStrategy;
use SlmQueue\Worker\Event\ProcessQueueEvent;
use SlmQueue\Worker\Event\WorkerEventInterface;
use SlmQueue\Worker\Result\ExitWorkerLoopResult;
use SlmQueueTest\Asset\SimpleWorker;
use SlmQueueTest\Util\ListenerAttachmentAssertions;

class InterruptStrategyTest extends TestCase
{
    use ListenerAttachmentAssertions;

    protected $queue;
    protected $worker;
    /** @var InterruptStrategy */
    protected $listener;

    public function setUp(): void
    {
        $this->queue = $this->createStub(QueueInterface::class);
        $this->worker = new SimpleWorker();
        $this->listener = new InterruptStrategy();
    }

    public function testListenerInstanceOfAbstractStrategy(): void
    {
        static::assertInstanceOf(AbstractStrategy::class, $this->listener);
    }

    public function testListensToCorrectEventAtCorrectPriority(): void
    {
        $priority = 1;

        $this->assertListenerAttachments($this->listener, $priority, [
            [WorkerEventInterface::EVENT_PROCESS_IDLE, [$this->listener, 'onStopConditionCheck'], $priority],
            [WorkerEventInterface::EVENT_PROCESS_QUEUE, [$this->listener, 'onStopConditionCheck'], -1000],
            [WorkerEventInterface::EVENT_PROCESS_STATE, [$this->listener, 'onReportQueueState'], $priority],
        ]);
    }

    public function testOnStopConditionCheckHandlerNoSignal(): void
    {
        $result = $this->listener->onStopConditionCheck(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertNull($result);
    }

    public function testOnStopConditionCheckHandlerSIGTERM(): void
    {
        $this->listener->onPCNTLSignal(SIGTERM);
        $result = $this->listener->onStopConditionCheck(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertInstanceOf(ExitWorkerLoopResult::class, $result);
    }

    public function testOnStopConditionCheckHandlerSIGINT(): void
    {
        $this->listener->onPCNTLSignal(SIGINT);
        $result = $this->listener->onStopConditionCheck(new ProcessQueueEvent($this->worker, $this->queue));
        static::assertInstanceOf(ExitWorkerLoopResult::class, $result);
    }
}
